import './livewire-calendar.css';
import { DateTime } from 'luxon';

declare global {
    interface Window {
        Livewire?: {
            find(id: string): Wire | undefined;
            hook(name: string, callback: (payload: { el: HTMLElement }) => void): void;
        };
    }
}

interface Wire {
    $call(method: string, ...params: unknown[]): Promise<unknown>;
}

const ROOT_SELECTOR = '[data-livewire-calendar-root]';
const INITIALIZED_ATTR = 'data-livewire-calendar-initialized';
const TIME_ZONE_ATTR = 'data-livewire-calendar-time-zone';
const EVENT_TIME_MANAGEMENT_ATTR = 'data-livewire-calendar-event-time-management-enabled';
const TOTAL_CELLS = 42;
const scrollSignatures = new WeakMap<HTMLElement, string>();

let pendingDrag: {
    eventEl: HTMLElement;
    eventId: string;
    startIso: string;
    endIso: string;
    durationMin: number;
    root: HTMLElement;
    allDay: boolean;
} | null = null;

let pendingResize: {
    eventEl: HTMLElement;
    eventId: string;
    startIso: string;
    startMin: number;
    endMin: number;
    dateStr: string;
    root: HTMLElement;
} | null = null;

let pendingSelect: {
    startDate: string;
    startMinute: number;
    endMinute: number;
    root: HTMLElement;
} | null = null;

let interactionMoved = false;
let suppressNextClick = false;

let dragDropTarget: HTMLElement | null = null;
let dragGhost: HTMLElement | null = null;
let dragOffsetX = 0;
let dragOffsetY = 0;
let livewireMorphHookRegistered = false;

function getCalendarTimeZone(root: HTMLElement): string {
    const zone = root.getAttribute(TIME_ZONE_ATTR);
    return zone && zone.length > 0 ? zone : 'UTC';
}

function isEventTimeManagementEnabled(root: HTMLElement): boolean {
    const raw = root.getAttribute(EVENT_TIME_MANAGEMENT_ATTR);
    if (!raw) return true;

    return raw !== 'false' && raw !== '0';
}

function getWire(root: HTMLElement): Wire | undefined {
    const wireEl = root.closest('[wire\\:id]');
    if (!wireEl) return undefined;
    const wireId = wireEl.getAttribute('wire:id');
    if (!wireId) return undefined;
    return window.Livewire?.find(wireId);
}

function getTimeGridBody(root: HTMLElement): HTMLElement | null {
    const view = root.getAttribute('data-livewire-calendar-view');

    if (view === 'timeGridWeek' || view === 'timeGridDay' || view === 'resourceTimeGridDay') {
        return root.querySelector<HTMLElement>('.lec-timegrid-body');
    }

    return null;
}

function getEarliestTimedEvent(root: HTMLElement): HTMLElement | null {
    let earliestEvent: HTMLElement | null = null;

    root.querySelectorAll<HTMLElement>('.lec-timed-event[data-start-min][data-event-id]').forEach((event) => {
        if (!earliestEvent || Number(event.dataset.startMin) < Number(earliestEvent.dataset.startMin)) {
            earliestEvent = event;
        }
    });

    return earliestEvent;
}

function getScrollSignature(root: HTMLElement, event: HTMLElement): string {
    return [
        root.getAttribute('data-livewire-calendar-view') || '',
        root.getAttribute('data-livewire-calendar-initial-date') || '',
        event.dataset.eventId || '',
        event.dataset.startMin || '',
    ].join('|');
}

function scrollTimeGrid(root: HTMLElement): void {
    const body = getTimeGridBody(root);
    if (!body) return;

    const event = getEarliestTimedEvent(root);
    if (!event) return;

    const signature = getScrollSignature(root, event);
    if (scrollSignatures.get(root) === signature) return;

    const slot = body.querySelector<HTMLElement>('.lec-timegrid-slot');
    if (!slot) return;

    const slotHeight = slot.getBoundingClientRect().height;
    const startMinute = Number(event.dataset.startMin);

    body.scrollTop = Math.max(0, (startMinute / 30) * slotHeight - (slotHeight * 2));
    scrollSignatures.set(root, signature);
}

function scheduleTimeGridScroll(root: HTMLElement): void {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => scrollTimeGrid(root));
    });
}

function isDateOnlyIso(value: string): boolean {
    return /^\d{4}-\d{2}-\d{2}$/.test(value);
}

function parseIsoInZone(iso: string, zone: string): DateTime {
    if (isDateOnlyIso(iso)) {
        return DateTime.fromISO(iso, { zone });
    }

    const hasExplicitOffset = /([zZ]|[+-]\d{2}:?\d{2})$/.test(iso);
    if (hasExplicitOffset) {
        return DateTime.fromISO(iso, { setZone: true }).setZone(zone);
    }

    return DateTime.fromISO(iso, { zone });
}

function isoLocalNoOffset(dt: DateTime): string {
    return dt.toFormat("yyyy-MM-dd'T'HH:mm:ss");
}

function ensureRemoveDropTargetClass(): void {
    if (dragDropTarget) {
        dragDropTarget.classList.remove('lec-drop-target');
        dragDropTarget = null;
    } else {
        document.querySelectorAll('.lec-drop-target').forEach((el) => el.classList.remove('lec-drop-target'));
    }
}

function highlightDropTarget(e: MouseEvent): void {
    if (!pendingDrag) return;

    const root = pendingDrag.root;
    const view = root.getAttribute('data-livewire-calendar-view') || 'month';

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement | null;
    let nextTarget: HTMLElement | null = null;

    if (target) {
        if (view === 'timeGridWeek' || view === 'timeGridDay' || view === 'resourceTimeGridDay') {
            nextTarget = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
        } else if (view === 'month') {
            nextTarget = target.closest('.lec-day-cell[data-date]') as HTMLElement;
        } else if (view === 'listWeek') {
            nextTarget = target.closest('.lec-list-day-group[data-date]') as HTMLElement;
        } else if (view === 'resourceTimelineDay') {
            nextTarget = (target.closest('.lec-resource-timeline-axis-tick[data-minute]')
                || target.closest('.lec-resource-timeline-lane[data-resource-id]')) as HTMLElement;
        }
    }

    if (nextTarget && !root.contains(nextTarget)) {
        nextTarget = null;
    }

    if (dragDropTarget && dragDropTarget !== nextTarget) {
        dragDropTarget.classList.remove('lec-drop-target');
    }

    if (nextTarget && dragDropTarget !== nextTarget) {
        nextTarget.classList.add('lec-drop-target');
    }

    dragDropTarget = nextTarget;
}

function startDrag(e: MouseEvent, eventEl: HTMLElement): void {
    const root = eventEl.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const zone = getCalendarTimeZone(root);

    const startIso = eventEl.getAttribute('data-event-start');
    const endIso = eventEl.getAttribute('data-event-end');
    if (!startIso || !endIso) return;

    const allDay = eventEl.getAttribute('data-all-day') === 'true';
    if (allDay) return;

    const originalStart = parseIsoInZone(startIso, zone);
    const originalEnd = parseIsoInZone(endIso, zone);
    if (!originalStart.isValid || !originalEnd.isValid) return;

    const durationMin = Math.round(originalEnd.diff(originalStart, 'minutes').minutes);
    if (durationMin <= 0) return;

    const rect = eventEl.getBoundingClientRect();
    dragOffsetX = e.clientX - rect.left;
    dragOffsetY = e.clientY - rect.top;

    if (dragGhost) {
        dragGhost.remove();
        dragGhost = null;
    }

    ensureRemoveDropTargetClass();

    pendingDrag = {
        eventEl,
        eventId: eventEl.getAttribute('data-event-id') || '',
        startIso,
        endIso,
        durationMin,
        root,
        allDay,
    };
    interactionMoved = false;
}

function handleDragMove(e: MouseEvent): void {
    if (!pendingDrag) return;

    if (!interactionMoved) {
        interactionMoved = true;
        pendingDrag.eventEl.classList.add('lec-event--dragging');

        const titleEl = pendingDrag.eventEl.querySelector<HTMLElement>('.lec-timed-event-title, .lec-list-event-title');
        const label = (titleEl?.textContent ?? pendingDrag.eventEl.textContent ?? '').trim();

        const ghost = document.createElement('div');
        ghost.className = 'lec-drag-ghost';
        ghost.textContent = label;

        const srcRect = pendingDrag.eventEl.getBoundingClientRect();
        const width = Math.min(320, Math.max(140, srcRect.width));
        ghost.style.width = `${width}px`;

        ghost.style.left = `${e.clientX - dragOffsetX}px`;
        ghost.style.top = `${e.clientY - dragOffsetY}px`;

        document.body.appendChild(ghost);
        dragGhost = ghost;
    }

    if (dragGhost) {
        dragGhost.style.left = `${e.clientX - dragOffsetX}px`;
        dragGhost.style.top = `${e.clientY - dragOffsetY}px`;
    }

    highlightDropTarget(e);
}

function getTimeGridRange(root: HTMLElement, zone: string): { rangeStart: string; rangeEnd: string } | null {
    const dayBodies = Array.from(root.querySelectorAll<HTMLElement>('.lec-timegrid-day-body[data-date]'));
    if (dayBodies.length === 0) return null;

    const dates = dayBodies
        .map((el) => el.getAttribute('data-date'))
        .filter((d): d is string => typeof d === 'string' && d.length > 0)
        .sort();

    if (dates.length === 0) return null;

    const rangeStart = dates[0];
    const rangeEnd = DateTime.fromISO(dates[dates.length - 1], { zone }).plus({ days: 1 }).toFormat('yyyy-MM-dd');
    return { rangeStart, rangeEnd };
}

function handleDragEnd(e: MouseEvent): void {
    if (!pendingDrag || !interactionMoved) return;

    const root = pendingDrag.root;
    const view = root.getAttribute('data-livewire-calendar-view') || 'month';

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement | null;
    if (!target) return;

    const $wire = getWire(root);
    if (!$wire) return;

    const zone = getCalendarTimeZone(root);

    if (view === 'timeGridWeek' || view === 'timeGridDay' || view === 'resourceTimeGridDay') {
        const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
        if (!slotCell) return;

        const newDateStr = slotCell.getAttribute('data-date') || '';
        const newMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

        const newStart = DateTime.fromISO(newDateStr, { zone }).set({
            hour: Math.floor(newMinute / 60),
            minute: newMinute % 60,
            second: 0,
            millisecond: 0,
        });
        const newEnd = newStart.plus({ minutes: pendingDrag.durationMin });

        const range = getTimeGridRange(root, zone);
        if (!range) return;

        $wire.$call('eventDrop', pendingDrag.eventId, isoLocalNoOffset(newStart), isoLocalNoOffset(newEnd), range.rangeStart, range.rangeEnd);
        return;
    }

    if (view === 'month') {
        const dayCell = target.closest('.lec-day-cell[data-date]') as HTMLElement;
        if (!dayCell) return;

        const newDateStr = dayCell.getAttribute('data-date') || '';
        if (newDateStr.length === 0) return;

        const originalStart = parseIsoInZone(pendingDrag.startIso, zone);
        if (!originalStart.isValid) return;

        const newStart = DateTime.fromISO(newDateStr, { zone }).set({
            hour: originalStart.hour,
            minute: originalStart.minute,
            second: originalStart.second,
            millisecond: 0,
        });
        const newEnd = newStart.plus({ minutes: pendingDrag.durationMin });

        const grid = root.querySelector<HTMLElement>('[data-testid="month-grid"]');
        if (!grid) return;

        const firstCell = grid.querySelector<HTMLElement>('.lec-day-cell[data-date]');
        const gridStartStr = firstCell?.getAttribute('data-date');
        if (!gridStartStr) return;

        const rangeStart = gridStartStr;
        const rangeEnd = DateTime.fromISO(rangeStart, { zone }).plus({ days: TOTAL_CELLS }).toFormat('yyyy-MM-dd');

        $wire.$call('eventDrop', pendingDrag.eventId, isoLocalNoOffset(newStart), isoLocalNoOffset(newEnd), rangeStart, rangeEnd);
        return;
    }

    if (view === 'listWeek') {
        const dayGroup = target.closest('.lec-list-day-group[data-date]') as HTMLElement;
        if (!dayGroup) return;

        const newDateStr = dayGroup.getAttribute('data-date') || '';
        if (newDateStr.length === 0) return;

        const originalStart = parseIsoInZone(pendingDrag.startIso, zone);
        if (!originalStart.isValid) return;

        const newStart = DateTime.fromISO(newDateStr, { zone }).set({
            hour: originalStart.hour,
            minute: originalStart.minute,
            second: originalStart.second,
            millisecond: 0,
        });
        const newEnd = newStart.plus({ minutes: pendingDrag.durationMin });

        const listContainer = root.querySelector<HTMLElement>('[data-testid="list-view"]');
        const rangeStart = listContainer?.getAttribute('data-range-start');
        const rangeEnd = listContainer?.getAttribute('data-range-end');
        if (!rangeStart || !rangeEnd) return;

        $wire.$call('eventDrop', pendingDrag.eventId, isoLocalNoOffset(newStart), isoLocalNoOffset(newEnd), rangeStart, rangeEnd);
        return;
    }

    if (view === 'resourceTimelineDay') {
        const tick = target.closest('.lec-resource-timeline-axis-tick[data-minute]') as HTMLElement;
        const lane = target.closest('.lec-resource-timeline-lane[data-resource-id]') as HTMLElement;
        if (!tick && !lane) return;

        let startMin = 0;
        if (tick) {
            startMin = parseInt(tick.getAttribute('data-minute') || '0', 10);
        } else if (lane) {
            const rect = lane.getBoundingClientRect();
            if (rect.width <= 0) return;
            const percent = (e.clientX - rect.left) / rect.width;
            startMin = Math.round((percent * 1440) / 30) * 30;
        }

        const durationMin = pendingDrag.durationMin;
        const maxStartMin = Math.max(0, 1440 - durationMin);
        startMin = Math.min(maxStartMin, Math.max(0, startMin));

        const timeline = root.querySelector<HTMLElement>('[data-testid="resource-timeline"]');
        if (!timeline) return;

        const dayStr = timeline.getAttribute('data-date');
        if (!dayStr) return;

        const dayStart = DateTime.fromISO(dayStr, { zone }).startOf('day');
        const dayEnd = dayStart.plus({ days: 1 });

        const newStart = dayStart.set({
            hour: Math.floor(startMin / 60),
            minute: startMin % 60,
            second: 0,
            millisecond: 0,
        });
        const newEnd = newStart.plus({ minutes: durationMin });

        $wire.$call('eventDrop', pendingDrag.eventId, isoLocalNoOffset(newStart), isoLocalNoOffset(newEnd), dayStr, dayEnd.toFormat('yyyy-MM-dd'));
    }
}

function startResize(e: MouseEvent, handleEl: HTMLElement): void {
    const eventEl = handleEl.closest('.lec-timed-event') as HTMLElement;
    if (!eventEl) return;

    const root = eventEl.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    pendingResize = {
        eventEl,
        eventId: eventEl.getAttribute('data-event-id') || '',
        startIso: eventEl.getAttribute('data-event-start') || '',
        startMin: parseInt(eventEl.getAttribute('data-start-min') || '0', 10),
        endMin: parseInt(eventEl.getAttribute('data-end-min') || '0', 10),
        dateStr: eventEl.getAttribute('data-date') || '',
        root,
    };
    interactionMoved = false;
}

function handleResizeMove(e: MouseEvent): void {
    if (!pendingResize) return;

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement | null;
    if (!target) return;
    const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
    if (!slotCell) return;

    const targetDate = slotCell.getAttribute('data-date') || '';
    if (targetDate !== pendingResize.dateStr) return;

    const targetMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);
    const newEndMin = targetMinute + 30;
    if (newEndMin <= pendingResize.startMin) return;

    if (!interactionMoved) {
        interactionMoved = true;
        pendingResize.eventEl.classList.add('lec-event--resizing');
    }

    pendingResize.endMin = newEndMin;
    pendingResize.eventEl.style.setProperty('--lec-end-min', String(newEndMin));
    pendingResize.eventEl.setAttribute('data-end-min', String(newEndMin));
}

function handleResizeEnd(): void {
    if (!pendingResize || !interactionMoved) return;

    pendingResize.eventEl.classList.remove('lec-event--resizing');

    const root = pendingResize.root;
    const zone = getCalendarTimeZone(root);
    const range = getTimeGridRange(root, zone);
    if (!range) return;

    const dayStart = DateTime.fromISO(pendingResize.dateStr, { zone });
    const newEnd = dayStart.set({
        hour: Math.floor(pendingResize.endMin / 60),
        minute: pendingResize.endMin % 60,
        second: 0,
        millisecond: 0,
    });

    const startDt = parseIsoInZone(pendingResize.startIso, zone);
    if (!startDt.isValid) return;

    const $wire = getWire(root);
    if (!$wire) return;

    $wire.$call('eventResize', pendingResize.eventId, isoLocalNoOffset(startDt), isoLocalNoOffset(newEnd), range.rangeStart, range.rangeEnd);
}

function startSelect(slotCell: HTMLElement): void {
    const root = slotCell.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const dateStr = slotCell.getAttribute('data-date') || '';
    const minute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

    pendingSelect = {
        startDate: dateStr,
        startMinute: minute,
        endMinute: minute + 30,
        root,
    };

    interactionMoved = false;
}

function handleSelectMove(e: MouseEvent): void {
    if (!pendingSelect) return;

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement | null;
    if (!target) return;
    const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
    if (!slotCell) return;

    const targetDate = slotCell.getAttribute('data-date') || '';
    if (targetDate !== pendingSelect.startDate) return;

    const targetMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

    if (!interactionMoved) interactionMoved = true;

    const minMinute = Math.min(pendingSelect.startMinute, targetMinute);
    const maxMinute = Math.max(pendingSelect.startMinute, targetMinute) + 30;

    pendingSelect.startMinute = minMinute;
    pendingSelect.endMinute = maxMinute;
}

function handleSelectEnd(): void {
    if (!pendingSelect) return;

    const $wire = getWire(pendingSelect.root);
    if (!$wire) return;

    const zone = getCalendarTimeZone(pendingSelect.root);

    const startDt = DateTime.fromISO(pendingSelect.startDate, { zone }).set({
        hour: Math.floor(pendingSelect.startMinute / 60),
        minute: pendingSelect.startMinute % 60,
        second: 0,
        millisecond: 0,
    });
    const endDt = DateTime.fromISO(pendingSelect.startDate, { zone }).set({
        hour: Math.floor(pendingSelect.endMinute / 60),
        minute: pendingSelect.endMinute % 60,
        second: 0,
        millisecond: 0,
    });

    $wire.$call('dateSelect', isoLocalNoOffset(startDt), isoLocalNoOffset(endDt), false);
}

function handleTimedEventClick(eventEl: HTMLElement): void {
    const root = eventEl.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const eventId = eventEl.getAttribute('data-event-id') || '';
    const eventData = {
        id: eventId,
        title: eventEl.querySelector('.lec-timed-event-title')?.textContent || eventEl.textContent || '',
        start: eventEl.getAttribute('data-event-start') || '',
        end: eventEl.getAttribute('data-event-end') || '',
    };

    const $wire = getWire(root);
    $wire?.$call('eventClick', eventId, eventData);
}

document.addEventListener('mousedown', (e) => {
    const target = e.target as HTMLElement;
    if (!target) return;

    if (target.classList.contains('lec-resize-handle')) {
        const eventEl = target.closest('.lec-timed-event') as HTMLElement;
        const root = eventEl?.closest(ROOT_SELECTOR) as HTMLElement;
        if (!root || !isEventTimeManagementEnabled(root)) {
            return;
        }

        e.preventDefault();
        startResize(e, target);
        return;
    }

    const timedEvent = target.closest('.lec-timed-event') as HTMLElement;
    if (timedEvent) {
        const root = timedEvent.closest(ROOT_SELECTOR) as HTMLElement;
        if (!root || !isEventTimeManagementEnabled(root)) {
            return;
        }

        e.preventDefault();
        startDrag(e, timedEvent);
        return;
    }

    const monthEvent = target.closest('.lec-event') as HTMLElement;
    if (monthEvent) {
        const root = monthEvent.closest(ROOT_SELECTOR) as HTMLElement;
        if (!root || !isEventTimeManagementEnabled(root)) {
            return;
        }

        e.preventDefault();
        startDrag(e, monthEvent);
        return;
    }

    const listEvent = target.closest('.lec-list-event') as HTMLElement;
    if (listEvent) {
        const root = listEvent.closest(ROOT_SELECTOR) as HTMLElement;
        if (!root || !isEventTimeManagementEnabled(root)) {
            return;
        }

        e.preventDefault();
        startDrag(e, listEvent);
        return;
    }

    const resourceEvent = target.closest('.lec-resource-timeline-event') as HTMLElement;
    if (resourceEvent) {
        const root = resourceEvent.closest(ROOT_SELECTOR) as HTMLElement;
        if (!root || !isEventTimeManagementEnabled(root)) {
            return;
        }

        e.preventDefault();
        startDrag(e, resourceEvent);
        return;
    }

    if (target.classList.contains('lec-slot-cell') && target.hasAttribute('data-date')) {
        e.preventDefault();
        startSelect(target);
    }
});

document.addEventListener('mousemove', (e) => {
    if (pendingDrag) {
        handleDragMove(e);
    } else if (pendingResize) {
        handleResizeMove(e);
    } else if (pendingSelect) {
        handleSelectMove(e);
    }
});

document.addEventListener('mouseup', (e) => {
    if (pendingDrag && interactionMoved) {
        handleDragEnd(e);
        suppressNextClick = true;
    }
    if (pendingDrag) {
        pendingDrag.eventEl.classList.remove('lec-event--dragging');
    }

    if (dragGhost) {
        dragGhost.remove();
        dragGhost = null;
    }

    ensureRemoveDropTargetClass();

    if (pendingResize && interactionMoved) {
        handleResizeEnd();
        suppressNextClick = true;
    }
    if (pendingResize) {
        pendingResize.eventEl.classList.remove('lec-event--resizing');
    }

    if (pendingSelect) {
        handleSelectEnd();
    }

    pendingDrag = null;
    pendingResize = null;
    pendingSelect = null;
    interactionMoved = false;
});

document.addEventListener('click', (e) => {
    if (suppressNextClick) {
        suppressNextClick = false;
        return;
    }

    const target = e.target as HTMLElement;
    if (!target) return;

    const timedEvent = target.closest('.lec-timed-event') as HTMLElement;
    if (timedEvent) {
        handleTimedEventClick(timedEvent);
    }
});

function initializeRoots(): void {
    document.querySelectorAll<HTMLElement>(ROOT_SELECTOR).forEach((root) => {
        if (root.getAttribute(INITIALIZED_ATTR) !== 'true') {
            root.setAttribute(INITIALIZED_ATTR, 'true');
        }

        scheduleTimeGridScroll(root);
    });
}

function registerLivewireMorphHook(): void {
    if (livewireMorphHookRegistered || !window.Livewire) {
        return;
    }

    window.Livewire.hook('morphed', ({ el }: { el: HTMLElement }) => {
        const root = el.matches(ROOT_SELECTOR)
            ? el
            : el.querySelector<HTMLElement>(ROOT_SELECTOR);

        if (root) {
            scheduleTimeGridScroll(root);
        }
    });

    livewireMorphHookRegistered = true;
}

initializeRoots();

document.addEventListener('livewire:navigated', initializeRoots);
document.addEventListener('livewire:initialized', () => {
    initializeRoots();
    registerLivewireMorphHook();
});
document.addEventListener('livewire:init', () => {
    registerLivewireMorphHook();
});
registerLivewireMorphHook();
