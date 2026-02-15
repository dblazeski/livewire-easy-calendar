import './livewire-calendar.css';
import { DateTime, Info } from 'luxon';
import { RRule, RRuleSet } from 'rrule';

declare global {
    interface Window {
        Livewire?: {
            find(id: string): Wire | undefined;
        };
    }
}

interface Wire {
    $call(method: string, ...params: unknown[]): Promise<unknown>;
}

interface CalendarEventData {
    id: string;
    title: string;
    start: string;
    end: string;
    [key: string]: unknown;
}

interface CalendarResourceData {
    id: string;
    title: string;
    [key: string]: unknown;
}

interface TimedSegment {
    event: CalendarEventData;
    dateStr: string;
    startMin: number;
    endMin: number;
}

const ROOT_SELECTOR = '[data-livewire-calendar-root]';
const INITIALIZED_ATTR = 'data-livewire-calendar-initialized';
const TOTAL_CELLS = 42;
const TIME_ZONE_ATTR = 'data-livewire-calendar-time-zone';

const renderTokens = new WeakMap<HTMLElement, number>();

interface RootContext {
    eventsLayer: HTMLElement;
    alldayRow: HTMLElement;
    days: DateTime[];
}

const rootContexts = new WeakMap<HTMLElement, RootContext>();

let pendingDrag: {
    eventEl: HTMLElement; eventId: string; startIso: string; endIso: string;
    durationMin: number; root: HTMLElement;
} | null = null;

let pendingResize: {
    eventEl: HTMLElement; eventId: string; startIso: string;
    startMin: number; endMin: number; dateStr: string; root: HTMLElement;
} | null = null;

let pendingSelect: {
    startDate: string; startMinute: number; dayBody: HTMLElement;
    selectionEl: HTMLElement; root: HTMLElement;
} | null = null;

let interactionMoved = false;
let suppressNextClick = false;

function nextRenderToken(root: HTMLElement): number {
    const next = (renderTokens.get(root) ?? 0) + 1;
    renderTokens.set(root, next);
    return next;
}

function getRenderToken(root: HTMLElement): number {
    return renderTokens.get(root) ?? 0;
}

function getOrderedWeekdays(firstDay: number): string[] {
    const weekdays = Info.weekdays('short');
    const luxonStartIndex = (firstDay + 6) % 7;
    return [...weekdays.slice(luxonStartIndex), ...weekdays.slice(0, luxonStartIndex)];
}

function getCalendarTimeZone(root: HTMLElement): string {
    const zone = root.getAttribute(TIME_ZONE_ATTR);
    return zone && zone.length > 0 ? zone : 'UTC';
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

function toRRuleUtcFieldsDate(dt: DateTime): Date {
    return new Date(Date.UTC(
        dt.year,
        dt.month - 1,
        dt.day,
        dt.hour,
        dt.minute,
        dt.second,
        dt.millisecond,
    ));
}

function fromRRuleUtcFieldsDate(date: Date, zone: string): DateTime {
    return DateTime.fromObject({
        year: date.getUTCFullYear(),
        month: date.getUTCMonth() + 1,
        day: date.getUTCDate(),
        hour: date.getUTCHours(),
        minute: date.getUTCMinutes(),
        second: date.getUTCSeconds(),
        millisecond: date.getUTCMilliseconds(),
    }, { zone });
}

function buildRRuleSet(rruleValue: string, dtstart: DateTime, zone: string, exdate: unknown): { between: (after: Date, before: Date, inc?: boolean) => Date[] } {
    const trimmed = rruleValue.trim();
    const lines = trimmed.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);

    const rruleLine = lines.find((l) => l.toUpperCase().startsWith('RRULE:'));
    const value = rruleLine
        ? rruleLine.slice('RRULE:'.length)
        : (trimmed.startsWith('RRULE:') ? trimmed.slice('RRULE:'.length) : trimmed);

    const options = RRule.parseString(value);
    options.dtstart = toRRuleUtcFieldsDate(dtstart);

    const set = new RRuleSet();
    set.rrule(new RRule(options));

    if (Array.isArray(exdate)) {
        for (const ex of exdate) {
            if (typeof ex !== 'string') continue;

            let exDt: DateTime;
            if (isDateOnlyIso(ex)) {
                exDt = DateTime.fromISO(ex, { zone }).set({
                    hour: dtstart.hour,
                    minute: dtstart.minute,
                    second: dtstart.second,
                    millisecond: 0,
                });
            } else {
                exDt = parseIsoInZone(ex, zone);
            }

            if (!exDt.isValid) continue;

            set.exdate(toRRuleUtcFieldsDate(exDt));
        }
    }

    return set;
}

function expandRecurringEvents(events: CalendarEventData[], rangeStart: DateTime, rangeEnd: DateTime, zone: string): CalendarEventData[] {
    const expanded: CalendarEventData[] = [];

    for (const event of events) {
        const rruleValue = typeof event.rrule === 'string' ? (event.rrule as string) : null;
        if (!rruleValue) {
            expanded.push(event);
            continue;
        }

        const baseStart = parseIsoInZone(event.start, zone);
        const baseEnd = parseIsoInZone(event.end, zone);
        if (!baseStart.isValid || !baseEnd.isValid) {
            expanded.push(event);
            continue;
        }

        const durationMs = baseEnd.toMillis() - baseStart.toMillis();
        const dtstart = baseStart;

        const ruleSet = buildRRuleSet(rruleValue, dtstart, zone, event.exdate);

        const occDates = ruleSet.between(
            toRRuleUtcFieldsDate(rangeStart),
            toRRuleUtcFieldsDate(rangeEnd),
            true,
        );

        for (const occDate of occDates) {
            const occStart = fromRRuleUtcFieldsDate(occDate, zone);

            if (occStart < rangeStart) continue;
            if (occStart >= rangeEnd) continue;

            const occEnd = occStart.plus({ milliseconds: durationMs });

            const occurrenceKey = isDateOnlyIso(event.start)
                ? occStart.toFormat('yyyyMMdd')
                : occStart.toFormat("yyyyMMdd'T'HHmmss");
            const occurrenceId = `${event.id}__${occurrenceKey}`;

            expanded.push({
                ...event,
                id: occurrenceId,
                recurrenceId: event.id,
                start: occStart.toISO({ suppressMilliseconds: true }) ?? event.start,
                end: occEnd.toISO({ suppressMilliseconds: true }) ?? event.end,
            });
        }
    }

    return expanded;
}

function wallClockMinuteInDayGrid(dt: DateTime, dayStart: DateTime, dayEnd: DateTime): number {
    if (dt <= dayStart) return 0;
    if (dt >= dayEnd) return 1440;

    return (dt.hour * 60) + dt.minute;
}

function computeGridStart(date: DateTime, firstDay: number): DateTime {
    const jsWeekday = date.weekday % 7;
    const daysBack = (jsWeekday - firstDay + 7) % 7;
    return date.minus({ days: daysBack });
}

function computeWeekStart(date: DateTime, firstDay: number): DateTime {
    return computeGridStart(date.startOf('day'), firstDay);
}

function getWire(root: HTMLElement): Wire | undefined {
    const wireEl = root.closest('[wire\\:id]');
    if (!wireEl) return undefined;
    const wireId = wireEl.getAttribute('wire:id');
    if (!wireId) return undefined;
    return window.Livewire?.find(wireId);
}

function loadEvents(root: HTMLElement, grid: HTMLElement, gridStart: DateTime, token: number): void {
    if (token !== getRenderToken(root)) return;

    const wireEl = root.closest('[wire\\:id]');
    if (!wireEl) return;

    const wireId = wireEl.getAttribute('wire:id');
    if (!wireId) return;

    const $wire = window.Livewire?.find(wireId);
    if (!$wire) return;

    const zone = getCalendarTimeZone(root);
    const rangeStart = gridStart.setZone(zone).startOf('day');
    const rangeEnd = rangeStart.plus({ days: TOTAL_CELLS });
    const startStr = rangeStart.toFormat('yyyy-MM-dd');
    const endStr = rangeEnd.toFormat('yyyy-MM-dd');

    $wire.$call('fetchEvents', startStr, endStr).then((result) => {
        if (token !== getRenderToken(root)) return;
        const expanded = expandRecurringEvents(result as CalendarEventData[], rangeStart, rangeEnd, zone);
        renderEvents(grid, expanded, zone);
    });
}

function renderEvents(grid: HTMLElement, events: CalendarEventData[], zone: string): void {
    for (const event of events) {
        const eventDate = parseIsoInZone(event.start, zone).toFormat('yyyy-MM-dd');
        const cell = grid.querySelector(`[data-date="${eventDate}"]`);
        if (!cell) continue;

        const eventEl = document.createElement('div');
        eventEl.className = 'lec-event';
        eventEl.setAttribute('data-event-id', event.id);
        eventEl.textContent = event.title;
        cell.appendChild(eventEl);
    }
}

function renderMonthView(root: HTMLElement, monthStart: DateTime, firstDay: number, token: number): void {
    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    title.textContent = monthStart.toFormat('LLLL yyyy');
    root.appendChild(title);

    const gridStart = computeGridStart(monthStart, firstDay);
    const currentMonth = monthStart.month;

    const grid = document.createElement('div');
    grid.setAttribute('data-testid', 'month-grid');
    grid.className = 'lec-month-grid';

    const orderedWeekdays = getOrderedWeekdays(firstDay);
    for (const dayName of orderedWeekdays) {
        const header = document.createElement('div');
        header.className = 'lec-dow-header';
        header.textContent = dayName;
        grid.appendChild(header);
    }

    for (let i = 0; i < TOTAL_CELLS; i++) {
        const cellDate = gridStart.plus({ days: i });
        const dateStr = cellDate.toFormat('yyyy-MM-dd');
        const isCurrentMonth = cellDate.month === currentMonth;

        const cell = document.createElement('div');
        cell.setAttribute('data-testid', `day-cell-${dateStr}`);
        cell.setAttribute('data-date', dateStr);
        cell.setAttribute('data-current-month', isCurrentMonth ? 'true' : 'false');
        cell.className = `lec-day-cell${isCurrentMonth ? '' : ' lec-day-cell--outside'}`;

        const dayNumber = document.createElement('span');
        dayNumber.className = 'lec-day-number';
        dayNumber.textContent = String(cellDate.day);
        cell.appendChild(dayNumber);

        grid.appendChild(cell);
    }

    root.appendChild(grid);

    loadEvents(root, grid, gridStart, token);
}

function loadTimeGridEvents(root: HTMLElement, eventsLayer: HTMLElement, alldayRow: HTMLElement, days: DateTime[], token: number): void {
    rootContexts.set(root, { eventsLayer, alldayRow, days });

    if (token !== getRenderToken(root)) return;

    const $wire = getWire(root);
    if (!$wire) return;

    const zone = getCalendarTimeZone(root);
    const rangeStart = days[0].setZone(zone).startOf('day');
    const rangeEnd = days[days.length - 1].setZone(zone).startOf('day').plus({ days: 1 });

    const startStr = rangeStart.toFormat('yyyy-MM-dd');
    const endStr = rangeEnd.toFormat('yyyy-MM-dd');

    $wire.$call('fetchEvents', startStr, endStr).then((result) => {
        if (token !== getRenderToken(root)) return;
        const expanded = expandRecurringEvents(result as CalendarEventData[], rangeStart, rangeEnd, zone);
        renderTimeGridEvents(root, eventsLayer, alldayRow, expanded, days, zone);
    });
}

function loadResourceTimelineResources(root: HTMLElement, timeline: HTMLElement, dayDate: DateTime, token: number): void {
    if (token !== getRenderToken(root)) return;

    const $wire = getWire(root);
    if (!$wire) return;

    const zone = getCalendarTimeZone(root);
    const rangeStart = dayDate.setZone(zone).startOf('day');
    const rangeEnd = rangeStart.plus({ days: 1 });

    const startStr = rangeStart.toFormat('yyyy-MM-dd');
    const endStr = rangeEnd.toFormat('yyyy-MM-dd');

    const resourcesPromise = $wire.$call('fetchResources', startStr, endStr);
    const eventsPromise = $wire.$call('fetchEvents', startStr, endStr);

    resourcesPromise.then((resourcesResult) => {
        if (token !== getRenderToken(root)) return;

        const resources = resourcesResult as CalendarResourceData[];
        renderResourceTimelineDayContents(timeline, resources);

        eventsPromise.then((eventsResult) => {
            if (token !== getRenderToken(root)) return;
            const expanded = expandRecurringEvents(eventsResult as CalendarEventData[], rangeStart, rangeEnd, zone);
            renderResourceTimelineDayEvents(timeline, expanded, rangeStart, rangeEnd, zone);
        });
    });
}

function renderResourceTimelineDayEvents(
    timeline: HTMLElement,
    events: CalendarEventData[],
    dayStart: DateTime,
    dayEnd: DateTime,
    zone: string,
): void {
    for (const event of events) {
        const resourceIdRaw = (event as { resourceId?: unknown }).resourceId;
        if (resourceIdRaw === null || resourceIdRaw === undefined) continue;

        const resourceId = String(resourceIdRaw);
        const lane = timeline.querySelector<HTMLElement>(`.lec-resource-timeline-lane[data-resource-id="${resourceId}"]`);
        if (!lane) continue;

        const eventStart = parseIsoInZone(event.start, zone);
        const eventEnd = parseIsoInZone(event.end, zone);
        if (!eventStart.isValid || !eventEnd.isValid) continue;

        if (eventStart >= dayEnd || eventEnd <= dayStart) continue;

        const segStart = eventStart < dayStart ? dayStart : eventStart;
        const segEnd = eventEnd > dayEnd ? dayEnd : eventEnd;

        const startMin = wallClockMinuteInDayGrid(segStart, dayStart, dayEnd);
        const endMin = wallClockMinuteInDayGrid(segEnd, dayStart, dayEnd);
        if (endMin <= startMin) continue;

        const eventEl = document.createElement('div');
        eventEl.className = 'lec-resource-timeline-event';
        eventEl.setAttribute('data-testid', `resource-event-${event.id}-${resourceId}`);
        eventEl.setAttribute('data-resource-id', resourceId);
        eventEl.setAttribute('data-start-min', String(startMin));
        eventEl.setAttribute('data-end-min', String(endMin));
        eventEl.setAttribute('data-event-id', event.id);
        eventEl.textContent = event.title;

        const leftPercent = (startMin / 1440) * 100;
        const widthPercent = ((endMin - startMin) / 1440) * 100;

        eventEl.style.position = 'absolute';
        eventEl.style.left = `${leftPercent}%`;
        eventEl.style.width = `${widthPercent}%`;
        eventEl.style.top = '0';
        eventEl.style.bottom = '0';

        lane.appendChild(eventEl);
    }
}

function renderResourceTimelineDayContents(
    timeline: HTMLElement,
    resources: CalendarResourceData[],
): void {
    timeline.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'lec-resource-timeline-header';

    const headerLeft = document.createElement('div');
    headerLeft.className = 'lec-resource-timeline-header-left';
    headerLeft.textContent = 'Resources';
    header.appendChild(headerLeft);

    const headerRight = document.createElement('div');
    headerRight.className = 'lec-resource-timeline-axis';

    for (let minutes = 0; minutes < 24 * 60; minutes += 30) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const timeStr = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

        const tick = document.createElement('div');
        tick.className = 'lec-resource-timeline-axis-tick';
        if (mins === 0) {
            tick.textContent = timeStr;
        }
        headerRight.appendChild(tick);
    }
    header.appendChild(headerRight);
    timeline.appendChild(header);

    const body = document.createElement('div');
    body.className = 'lec-resource-timeline-body';
    timeline.appendChild(body);

    for (const resource of resources) {
        const resourceId = String(resource.id);

        const row = document.createElement('div');
        row.className = 'lec-resource-timeline-row';
        row.setAttribute('data-testid', `resource-row-${resourceId}`);
        row.setAttribute('data-resource-id', resourceId);

        const label = document.createElement('div');
        label.className = 'lec-resource-timeline-label';
        label.setAttribute('data-testid', `resource-label-${resourceId}`);
        label.textContent = resource.title;
        row.appendChild(label);

        const lane = document.createElement('div');
        lane.className = 'lec-resource-timeline-lane';
        lane.setAttribute('data-resource-id', resourceId);
        row.appendChild(lane);

        body.appendChild(row);
    }
}

function computeOverlapColumns(segments: TimedSegment[]): Map<TimedSegment, { col: number; colCount: number }> {
    const result = new Map<TimedSegment, { col: number; colCount: number }>();
    if (segments.length === 0) return result;

    // Sort by startMin asc, then duration desc (longer first), then id for determinism
    const sorted = [...segments].sort((a, b) => {
        if (a.startMin !== b.startMin) return a.startMin - b.startMin;
        const aDur = a.endMin - a.startMin;
        const bDur = b.endMin - b.startMin;
        if (aDur !== bDur) return bDur - aDur;
        return a.event.id.localeCompare(b.event.id);
    });

    // Build overlap groups and assign columns
    let groupStart = 0;
    let groupEnd = sorted[0].endMin;

    for (let i = 1; i <= sorted.length; i++) {
        if (i < sorted.length && sorted[i].startMin < groupEnd) {
            groupEnd = Math.max(groupEnd, sorted[i].endMin);
            continue;
        }

        // Process group [groupStart, i)
        const group = sorted.slice(groupStart, i);
        const colEnds: number[] = [];

        for (const seg of group) {
            let assigned = -1;
            for (let c = 0; c < colEnds.length; c++) {
                if (seg.startMin >= colEnds[c]) {
                    assigned = c;
                    break;
                }
            }
            if (assigned === -1) {
                assigned = colEnds.length;
                colEnds.push(0);
            }
            colEnds[assigned] = seg.endMin;
            result.set(seg, { col: assigned, colCount: 0 });
        }

        const colCount = colEnds.length;
        for (const seg of group) {
            result.get(seg)!.colCount = colCount;
        }

        if (i < sorted.length) {
            groupStart = i;
            groupEnd = sorted[i].endMin;
        }
    }

    return result;
}

function renderTimeGridEvents(root: HTMLElement, eventsLayer: HTMLElement, alldayRow: HTMLElement, events: CalendarEventData[], days: DateTime[], zone: string): void {
    // Idempotency: clear previous events
    eventsLayer.querySelectorAll('.lec-timed-event').forEach(el => el.remove());
    alldayRow.querySelectorAll('.lec-allday-event').forEach(el => el.remove());

    // Separate all-day vs timed events
    const allDayEvents: CalendarEventData[] = [];
    const timedEvents: CalendarEventData[] = [];
    for (const event of events) {
        if (event.allDay === true) {
            allDayEvents.push(event);
        } else {
            timedEvents.push(event);
        }
    }

    // Render all-day events into allday cells
    for (const event of allDayEvents) {
        const eventStart = parseIsoInZone(event.start, zone);
        const eventEnd = parseIsoInZone(event.end, zone);

        for (const day of days) {
            const dayStart = day.startOf('day');
            const dayEnd = dayStart.plus({ days: 1 });

            if (eventStart >= dayEnd || eventEnd <= dayStart) continue;

            const dateStr = day.toFormat('yyyy-MM-dd');
            const cell = alldayRow.querySelector(`[data-date="${dateStr}"]`);
            if (!cell) continue;

            const eventEl = document.createElement('div');
            eventEl.className = 'lec-allday-event';
            eventEl.setAttribute('data-testid', `allday-event-${event.id}-${dateStr}`);
            eventEl.setAttribute('data-event-id', event.id);
            eventEl.setAttribute('data-date', dateStr);
            eventEl.textContent = event.title;
            cell.appendChild(eventEl);
        }
    }

    // Build timed event segments per day
    const daySegmentsMap = new Map<string, TimedSegment[]>();

    for (const event of timedEvents) {
        const eventStart = parseIsoInZone(event.start, zone);
        const eventEnd = parseIsoInZone(event.end, zone);

        for (const day of days) {
            const dayStart = day.startOf('day');
            const dayEnd = dayStart.plus({ days: 1 });

            if (eventStart >= dayEnd || eventEnd <= dayStart) continue;

            const segStart = eventStart < dayStart ? dayStart : eventStart;
            const segEnd = eventEnd > dayEnd ? dayEnd : eventEnd;

            const startMin = wallClockMinuteInDayGrid(segStart, dayStart, dayEnd);
            const endMin = wallClockMinuteInDayGrid(segEnd, dayStart, dayEnd);

            const dateStr = day.toFormat('yyyy-MM-dd');
            const seg: TimedSegment = { event, dateStr, startMin, endMin };

            if (!daySegmentsMap.has(dateStr)) {
                daySegmentsMap.set(dateStr, []);
            }
            daySegmentsMap.get(dateStr)!.push(seg);
        }
    }

    // Render timed events with overlap layout
    for (const [dateStr, segments] of daySegmentsMap) {
        const layout = computeOverlapColumns(segments);
        const dayBody = eventsLayer.querySelector(`[data-date="${dateStr}"]`);
        if (!dayBody) continue;

        for (const seg of segments) {
            const { col, colCount } = layout.get(seg)!;

            const eventEl = document.createElement('div');
            eventEl.className = 'lec-timed-event';
            eventEl.setAttribute('data-testid', `timed-event-${seg.event.id}-${dateStr}`);
            eventEl.setAttribute('data-event-id', seg.event.id);
            eventEl.setAttribute('data-date', dateStr);
            eventEl.setAttribute('data-start-min', String(seg.startMin));
            eventEl.setAttribute('data-end-min', String(seg.endMin));
            eventEl.setAttribute('data-col', String(col));
            eventEl.setAttribute('data-col-count', String(colCount));
            eventEl.setAttribute('data-event-start', seg.event.start);
            eventEl.setAttribute('data-event-end', seg.event.end);

            const titleSpan = document.createElement('span');
            titleSpan.className = 'lec-timed-event-title';
            titleSpan.textContent = seg.event.title;
            eventEl.appendChild(titleSpan);

            const resizeHandle = document.createElement('div');
            resizeHandle.className = 'lec-resize-handle';
            resizeHandle.setAttribute('data-testid', `timed-event-resize-handle-${seg.event.id}-${dateStr}`);
            eventEl.appendChild(resizeHandle);

            const topPercent = (seg.startMin / 1440) * 100;
            const heightPercent = ((seg.endMin - seg.startMin) / 1440) * 100;
            const leftPercent = (col / colCount) * 100;
            const widthPercent = (1 / colCount) * 100;

            eventEl.style.top = `${topPercent}%`;
            eventEl.style.height = `${heightPercent}%`;
            eventEl.style.left = `${leftPercent}%`;
            eventEl.style.width = `${widthPercent}%`;

            dayBody.appendChild(eventEl);
        }
    }
}

function renderTimeGrid(root: HTMLElement, days: DateTime[]): { eventsLayer: HTMLElement; alldayRow: HTMLElement } {
    const dayCount = days.length;

    const timegrid = document.createElement('div');
    timegrid.setAttribute('data-testid', 'timegrid');
    timegrid.className = 'lec-timegrid';
    timegrid.style.setProperty('--lec-day-count', String(dayCount));

    const header = document.createElement('div');
    header.className = 'lec-timegrid-header';

    const headerGutter = document.createElement('div');
    headerGutter.className = 'lec-timegrid-gutter';
    header.appendChild(headerGutter);

    for (const day of days) {
        const dateStr = day.toFormat('yyyy-MM-dd');
        const col = document.createElement('div');
        col.className = 'lec-timegrid-day-header';
        col.setAttribute('data-testid', `timegrid-day-${dateStr}`);
        col.textContent = day.toFormat('ccc d');
        header.appendChild(col);
    }
    timegrid.appendChild(header);

    const alldayRow = document.createElement('div');
    alldayRow.setAttribute('data-testid', 'allday-row');
    alldayRow.className = 'lec-allday-row';

    const alldayGutter = document.createElement('div');
    alldayGutter.className = 'lec-timegrid-gutter lec-allday-label';
    alldayGutter.textContent = 'all-day';
    alldayRow.appendChild(alldayGutter);

    for (const day of days) {
        const dateStr = day.toFormat('yyyy-MM-dd');
        const cell = document.createElement('div');
        cell.className = 'lec-allday-cell';
        cell.setAttribute('data-testid', `allday-cell-${dateStr}`);
        cell.setAttribute('data-date', dateStr);
        alldayRow.appendChild(cell);
    }
    timegrid.appendChild(alldayRow);

    const body = document.createElement('div');
    body.className = 'lec-timegrid-body';

    const bodyInner = document.createElement('div');
    bodyInner.className = 'lec-timegrid-body-inner';

    for (let minutes = 0; minutes < 24 * 60; minutes += 30) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const timeStr = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

        const row = document.createElement('div');
        row.className = 'lec-timegrid-slot';

        const label = document.createElement('div');
        label.className = 'lec-time-label';
        if (mins === 0) {
            label.setAttribute('data-testid', `time-label-${timeStr}`);
            label.textContent = timeStr;
        }
        row.appendChild(label);

        for (let di = 0; di < days.length; di++) {
            const slotDateStr = days[di].toFormat('yyyy-MM-dd');
            const cell = document.createElement('div');
            cell.className = 'lec-slot-cell';
            cell.setAttribute('data-testid', `slot-cell-${slotDateStr}-${timeStr}`);
            cell.setAttribute('data-date', slotDateStr);
            cell.setAttribute('data-minute', String(minutes));
            row.appendChild(cell);
        }
        bodyInner.appendChild(row);
    }

    const eventsLayer = document.createElement('div');
    eventsLayer.className = 'lec-timegrid-events-layer';

    for (const day of days) {
        const dateStr = day.toFormat('yyyy-MM-dd');
        const dayBody = document.createElement('div');
        dayBody.className = 'lec-timegrid-day-body';
        dayBody.setAttribute('data-testid', `timegrid-day-body-${dateStr}`);
        dayBody.setAttribute('data-date', dateStr);
        eventsLayer.appendChild(dayBody);
    }

    bodyInner.appendChild(eventsLayer);
    body.appendChild(bodyInner);
    timegrid.appendChild(body);

    root.appendChild(timegrid);

    return { eventsLayer, alldayRow };
}

function renderTimeGridWeek(root: HTMLElement, weekStart: DateTime, _firstDay: number, token: number): void {
    const weekEnd = weekStart.plus({ days: 6 });

    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    if (weekStart.month === weekEnd.month) {
        title.textContent = `${weekStart.toFormat('LLLL d')} \u2013 ${weekEnd.toFormat('d, yyyy')}`;
    } else if (weekStart.year === weekEnd.year) {
        title.textContent = `${weekStart.toFormat('LLL d')} \u2013 ${weekEnd.toFormat('LLL d, yyyy')}`;
    } else {
        title.textContent = `${weekStart.toFormat('LLL d, yyyy')} \u2013 ${weekEnd.toFormat('LLL d, yyyy')}`;
    }
    root.appendChild(title);

    const days: DateTime[] = [];
    for (let i = 0; i < 7; i++) {
        days.push(weekStart.plus({ days: i }));
    }

    const { eventsLayer, alldayRow } = renderTimeGrid(root, days);
    rootContexts.set(root, { eventsLayer, alldayRow, days });
    loadTimeGridEvents(root, eventsLayer, alldayRow, days, token);
}

function loadListEvents(root: HTMLElement, listContainer: HTMLElement, weekStart: DateTime, token: number): void {
    if (token !== getRenderToken(root)) return;

    const wireEl = root.closest('[wire\\:id]');
    if (!wireEl) return;

    const wireId = wireEl.getAttribute('wire:id');
    if (!wireId) return;

    const $wire = window.Livewire?.find(wireId);
    if (!$wire) return;

    const zone = getCalendarTimeZone(root);
    const rangeStart = weekStart.setZone(zone).startOf('day');
    const rangeEnd = rangeStart.plus({ days: 7 });

    const startStr = rangeStart.toFormat('yyyy-MM-dd');
    const endStr = rangeEnd.toFormat('yyyy-MM-dd');

    $wire.$call('fetchEvents', startStr, endStr).then((result) => {
        if (token !== getRenderToken(root)) return;
        const expanded = expandRecurringEvents(result as CalendarEventData[], rangeStart, rangeEnd, zone);
        renderListEvents(listContainer, expanded, weekStart, zone);
    });
}

function renderListEvents(listContainer: HTMLElement, events: CalendarEventData[], weekStart: DateTime, zone: string): void {
    listContainer.innerHTML = '';

    const sorted = [...events].sort((a, b) => {
        const aStart = parseIsoInZone(a.start, zone);
        const bStart = parseIsoInZone(b.start, zone);
        if (aStart < bStart) return -1;
        if (aStart > bStart) return 1;

        const aEnd = parseIsoInZone(a.end, zone);
        const bEnd = parseIsoInZone(b.end, zone);
        if (aEnd < bEnd) return -1;
        if (aEnd > bEnd) return 1;

        return a.id.localeCompare(b.id);
    });

    const dayMap = new Map<string, CalendarEventData[]>();
    for (let i = 0; i < 7; i++) {
        const day = weekStart.plus({ days: i });
        const dateStr = day.toFormat('yyyy-MM-dd');
        dayMap.set(dateStr, []);
    }

    for (const event of sorted) {
        const eventStart = DateTime.fromISO(event.start);
        const eventEnd = DateTime.fromISO(event.end);

        for (let i = 0; i < 7; i++) {
            const day = weekStart.plus({ days: i });
            const dayStart = day.startOf('day');
            const dayEnd = dayStart.plus({ days: 1 });
            const dateStr = day.toFormat('yyyy-MM-dd');

            if (eventStart >= dayEnd || eventEnd <= dayStart) continue;

            dayMap.get(dateStr)!.push(event);
        }
    }

    for (const [dateStr, dayEvents] of dayMap) {
        if (dayEvents.length === 0) continue;

        const dayGroup = document.createElement('div');
        dayGroup.className = 'lec-list-day-group';
        dayGroup.setAttribute('data-testid', `list-day-${dateStr}`);

        const dayHeading = document.createElement('div');
        dayHeading.className = 'lec-list-day-heading';
        const dayDate = DateTime.fromISO(dateStr, { zone });
        dayHeading.textContent = dayDate.toFormat('cccc, LLLL d, yyyy');
        dayGroup.appendChild(dayHeading);

        const seenIds = new Set<string>();
        for (const event of dayEvents) {
            if (seenIds.has(event.id)) continue;
            seenIds.add(event.id);

            const eventRow = document.createElement('div');
            eventRow.className = 'lec-list-event';
            eventRow.setAttribute('data-testid', `list-event-${event.id}`);
            eventRow.setAttribute('data-event-id', event.id);
            eventRow.setAttribute('data-date', dateStr);

            const eventTime = document.createElement('span');
            eventTime.className = 'lec-list-event-time';
            const startDt = parseIsoInZone(event.start, zone);
            const endDt = parseIsoInZone(event.end, zone);

            if (event.allDay === true) {
                eventTime.textContent = 'all-day';
            } else {
                eventTime.textContent = `${startDt.toFormat('HH:mm')} – ${endDt.toFormat('HH:mm')}`;
            }
            eventRow.appendChild(eventTime);

            const eventTitle = document.createElement('span');
            eventTitle.className = 'lec-list-event-title';
            eventTitle.textContent = event.title;
            eventRow.appendChild(eventTitle);

            dayGroup.appendChild(eventRow);
        }

        listContainer.appendChild(dayGroup);
    }
}

function renderListWeek(root: HTMLElement, weekStart: DateTime, firstDay: number, token: number): void {
    const weekEnd = weekStart.plus({ days: 6 });

    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    if (weekStart.month === weekEnd.month) {
        title.textContent = `${weekStart.toFormat('LLLL d')} \u2013 ${weekEnd.toFormat('d, yyyy')}`;
    } else if (weekStart.year === weekEnd.year) {
        title.textContent = `${weekStart.toFormat('LLL d')} \u2013 ${weekEnd.toFormat('LLL d, yyyy')}`;
    } else {
        title.textContent = `${weekStart.toFormat('LLL d, yyyy')} \u2013 ${weekEnd.toFormat('LLL d, yyyy')}`;
    }
    root.appendChild(title);

    const listContainer = document.createElement('div');
    listContainer.className = 'lec-list-view';
    listContainer.setAttribute('data-testid', 'list-view');
    listContainer.setAttribute('data-range-start', weekStart.toFormat('yyyy-MM-dd'));
    listContainer.setAttribute('data-range-end', weekStart.plus({ days: 7 }).toFormat('yyyy-MM-dd'));
    root.appendChild(listContainer);

    loadListEvents(root, listContainer, weekStart, token);
}

function renderMultiMonthYear(root: HTMLElement, yearStart: DateTime, firstDay: number, _token: number): void {
    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    title.textContent = yearStart.toFormat('yyyy');
    root.appendChild(title);

    const container = document.createElement('div');
    container.className = 'lec-multimonth-view';
    container.setAttribute('data-testid', 'multimonth-view');

    for (let m = 0; m < 12; m++) {
        const monthStart = yearStart.plus({ months: m });
        const monthKey = monthStart.toFormat('yyyy-MM');

        const monthContainer = document.createElement('div');
        monthContainer.className = 'lec-multimonth-month';
        monthContainer.setAttribute('data-testid', `multimonth-month-${monthKey}`);

        const monthTitle = document.createElement('div');
        monthTitle.className = 'lec-multimonth-title';
        monthTitle.textContent = monthStart.toFormat('LLLL');
        monthContainer.appendChild(monthTitle);

        const gridStart = computeGridStart(monthStart, firstDay);
        const currentMonth = monthStart.month;

        const grid = document.createElement('div');
        grid.className = 'lec-multimonth-grid';

        const orderedWeekdays = getOrderedWeekdays(firstDay);
        for (const dayName of orderedWeekdays) {
            const header = document.createElement('div');
            header.className = 'lec-multimonth-dow-header';
            header.textContent = dayName;
            grid.appendChild(header);
        }

        for (let i = 0; i < TOTAL_CELLS; i++) {
            const cellDate = gridStart.plus({ days: i });
            const dateStr = cellDate.toFormat('yyyy-MM-dd');
            const isCurrentMonth = cellDate.month === currentMonth;

            const cell = document.createElement('div');
            cell.setAttribute('data-date', dateStr);
            cell.setAttribute('data-current-month', isCurrentMonth ? 'true' : 'false');
            cell.className = `lec-multimonth-day${isCurrentMonth ? '' : ' lec-multimonth-day--outside'}`;
            cell.textContent = String(cellDate.day);

            grid.appendChild(cell);
        }

        monthContainer.appendChild(grid);
        container.appendChild(monthContainer);
    }

    root.appendChild(container);
}

function renderResourceTimelineDay(root: HTMLElement, dayDate: DateTime, token: number): void {
    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    title.textContent = dayDate.toFormat('LLLL d, yyyy');
    root.appendChild(title);

    const timeline = document.createElement('div');
    timeline.className = 'lec-resource-timeline';
    timeline.setAttribute('data-testid', 'resource-timeline');
    timeline.setAttribute('data-date', dayDate.toFormat('yyyy-MM-dd'));
    root.appendChild(timeline);

    loadResourceTimelineResources(root, timeline, dayDate, token);
}

function renderTimeGridDay(root: HTMLElement, dayDate: DateTime, token: number): void {
    const title = document.createElement('div');
    title.setAttribute('data-testid', 'calendar-title');
    title.className = 'lec-title';
    title.textContent = dayDate.toFormat('LLLL d, yyyy');
    root.appendChild(title);

    const { eventsLayer, alldayRow } = renderTimeGrid(root, [dayDate]);
    rootContexts.set(root, { eventsLayer, alldayRow, days: [dayDate] });
    loadTimeGridEvents(root, eventsLayer, alldayRow, [dayDate], token);
}

function renderCalendar(root: HTMLElement, anchorDate: DateTime, firstDay: number, view: string): void {
    const token = nextRenderToken(root);

    while (root.firstChild) {
        root.removeChild(root.firstChild);
    }

    const zone = getCalendarTimeZone(root);
    const todayStr = root.getAttribute('data-livewire-calendar-today');
    const todayDate = todayStr ? DateTime.fromISO(todayStr, { zone }) : DateTime.now().setZone(zone);

    const toolbar = document.createElement('div');
    toolbar.className = 'lec-toolbar';
    toolbar.setAttribute('data-testid', 'calendar-toolbar');

    const btnPrev = document.createElement('button');
    btnPrev.className = 'lec-toolbar-btn';
    btnPrev.setAttribute('data-testid', 'btn-prev');
    btnPrev.setAttribute('type', 'button');
    btnPrev.textContent = '\u2039';

    const btnToday = document.createElement('button');
    btnToday.className = 'lec-toolbar-btn';
    btnToday.setAttribute('data-testid', 'btn-today');
    btnToday.setAttribute('type', 'button');
    btnToday.textContent = 'Today';

    const btnNext = document.createElement('button');
    btnNext.className = 'lec-toolbar-btn';
    btnNext.setAttribute('data-testid', 'btn-next');
    btnNext.setAttribute('type', 'button');
    btnNext.textContent = '\u203A';

    if (view === 'timeGridDay' || view === 'resourceTimelineDay') {
        btnPrev.addEventListener('click', () => {
            renderCalendar(root, anchorDate.minus({ days: 1 }), firstDay, view);
        });
        btnNext.addEventListener('click', () => {
            renderCalendar(root, anchorDate.plus({ days: 1 }), firstDay, view);
        });
        btnToday.addEventListener('click', () => {
            renderCalendar(root, todayDate.startOf('day'), firstDay, view);
        });
    } else if (view === 'timeGridWeek' || view === 'listWeek') {
        btnPrev.addEventListener('click', () => {
            renderCalendar(root, anchorDate.minus({ days: 7 }), firstDay, view);
        });
        btnNext.addEventListener('click', () => {
            renderCalendar(root, anchorDate.plus({ days: 7 }), firstDay, view);
        });
        btnToday.addEventListener('click', () => {
            renderCalendar(root, computeWeekStart(todayDate, firstDay), firstDay, view);
        });
    } else if (view === 'multiMonthYear') {
        btnPrev.addEventListener('click', () => {
            renderCalendar(root, anchorDate.minus({ years: 1 }), firstDay, view);
        });
        btnNext.addEventListener('click', () => {
            renderCalendar(root, anchorDate.plus({ years: 1 }), firstDay, view);
        });
        btnToday.addEventListener('click', () => {
            renderCalendar(root, todayDate.startOf('year'), firstDay, view);
        });
    } else {
        btnPrev.addEventListener('click', () => {
            renderCalendar(root, anchorDate.minus({ months: 1 }), firstDay, view);
        });
        btnNext.addEventListener('click', () => {
            renderCalendar(root, anchorDate.plus({ months: 1 }), firstDay, view);
        });
        btnToday.addEventListener('click', () => {
            renderCalendar(root, todayDate.startOf('month'), firstDay, view);
        });
    }

    toolbar.appendChild(btnPrev);
    toolbar.appendChild(btnToday);
    toolbar.appendChild(btnNext);
    root.appendChild(toolbar);

    if (view === 'timeGridDay') {
        renderTimeGridDay(root, anchorDate, token);
    } else if (view === 'resourceTimelineDay') {
        renderResourceTimelineDay(root, anchorDate, token);
    } else if (view === 'timeGridWeek') {
        renderTimeGridWeek(root, anchorDate, firstDay, token);
    } else if (view === 'listWeek') {
        renderListWeek(root, anchorDate, firstDay, token);
    } else if (view === 'multiMonthYear') {
        renderMultiMonthYear(root, anchorDate, firstDay, token);
    } else {
        renderMonthView(root, anchorDate, firstDay, token);
    }
}

function handleEventClick(eventEl: HTMLElement): void {
    const root = eventEl.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const isSelected = eventEl.getAttribute('data-selected') === 'true';

    root.querySelectorAll('[data-selected="true"]').forEach(el => {
        el.removeAttribute('data-selected');
        el.classList.remove('lec-event--selected');
    });

    root.querySelectorAll('[data-testid="timegrid-selection"]').forEach(el => el.remove());

    if (!isSelected) {
        eventEl.setAttribute('data-selected', 'true');
        eventEl.classList.add('lec-event--selected');
    }

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

function startSelect(e: MouseEvent, slotCell: HTMLElement): void {
    const root = slotCell.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const zone = getCalendarTimeZone(root);

    const dateStr = slotCell.getAttribute('data-date') || '';
    const minute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

    const eventsLayer = root.querySelector('.lec-timegrid-events-layer') as HTMLElement;
    if (!eventsLayer) return;

    const dayBody = eventsLayer.querySelector(`[data-date="${dateStr}"]`) as HTMLElement;
    if (!dayBody) return;

    root.querySelectorAll('[data-testid="timegrid-selection"]').forEach(el => el.remove());
    root.querySelectorAll('[data-selected="true"]').forEach(el => {
        el.removeAttribute('data-selected');
        el.classList.remove('lec-event--selected');
    });

    const selectionEl = document.createElement('div');
    selectionEl.className = 'lec-timegrid-selection';
    selectionEl.setAttribute('data-testid', 'timegrid-selection');

    const startDt = DateTime.fromISO(dateStr, { zone }).set({
        hour: Math.floor(minute / 60),
        minute: minute % 60,
        second: 0, millisecond: 0,
    });
    const endDt = startDt.plus({ minutes: 30 });

    selectionEl.setAttribute('data-start', startDt.toFormat("yyyy-MM-dd'T'HH:mm:ss"));
    selectionEl.setAttribute('data-end', endDt.toFormat("yyyy-MM-dd'T'HH:mm:ss"));

    const topPercent = (minute / 1440) * 100;
    const heightPercent = (30 / 1440) * 100;
    selectionEl.style.position = 'absolute';
    selectionEl.style.top = `${topPercent}%`;
    selectionEl.style.height = `${heightPercent}%`;
    selectionEl.style.left = '0';
    selectionEl.style.right = '0';

    dayBody.appendChild(selectionEl);

    pendingSelect = { startDate: dateStr, startMinute: minute, dayBody, selectionEl, root };
    interactionMoved = false;
}

function handleSelectMove(e: MouseEvent): void {
    if (!pendingSelect) return;

    const zone = getCalendarTimeZone(pendingSelect.root);

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement;
    if (!target) return;
    const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
    if (!slotCell) return;

    const targetDate = slotCell.getAttribute('data-date') || '';
    if (targetDate !== pendingSelect.startDate) return;

    const targetMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

    if (!interactionMoved) interactionMoved = true;

    const minMinute = Math.min(pendingSelect.startMinute, targetMinute);
    const maxMinute = Math.max(pendingSelect.startMinute, targetMinute) + 30;

    const topPercent = (minMinute / 1440) * 100;
    const heightPercent = ((maxMinute - minMinute) / 1440) * 100;

    const startDt = DateTime.fromISO(pendingSelect.startDate, { zone }).set({
        hour: Math.floor(minMinute / 60),
        minute: minMinute % 60,
        second: 0, millisecond: 0,
    });
    const endDt = DateTime.fromISO(pendingSelect.startDate, { zone }).set({
        hour: Math.floor(maxMinute / 60),
        minute: maxMinute % 60,
        second: 0, millisecond: 0,
    });

    pendingSelect.selectionEl.style.top = `${topPercent}%`;
    pendingSelect.selectionEl.style.height = `${heightPercent}%`;
    pendingSelect.selectionEl.setAttribute('data-start', startDt.toFormat("yyyy-MM-dd'T'HH:mm:ss"));
    pendingSelect.selectionEl.setAttribute('data-end', endDt.toFormat("yyyy-MM-dd'T'HH:mm:ss"));
}

function handleSelectEnd(): void {
    if (!pendingSelect) return;

    const startIso = pendingSelect.selectionEl.getAttribute('data-start') || '';
    const endIso = pendingSelect.selectionEl.getAttribute('data-end') || '';

    const $wire = getWire(pendingSelect.root);
    $wire?.$call('dateSelect', startIso, endIso, false);
}

function startDrag(e: MouseEvent, eventEl: HTMLElement): void {
    const root = eventEl.closest(ROOT_SELECTOR) as HTMLElement;
    if (!root) return;

    const zone = getCalendarTimeZone(root);

    const startIso = eventEl.getAttribute('data-event-start') || '';
    const endIso = eventEl.getAttribute('data-event-end') || '';

    const originalStart = parseIsoInZone(startIso, zone);
    const originalEnd = parseIsoInZone(endIso, zone);
    const durationMin = Math.round(originalEnd.diff(originalStart, 'minutes').minutes);

    pendingDrag = {
        eventEl,
        eventId: eventEl.getAttribute('data-event-id') || '',
        startIso, endIso, durationMin, root,
    };
    interactionMoved = false;
}

function handleDragMove(_e: MouseEvent): void {
    if (!pendingDrag) return;
    if (!interactionMoved) {
        interactionMoved = true;
        pendingDrag.eventEl.classList.add('lec-event--dragging');
    }
}

function handleDragEnd(e: MouseEvent): void {
    if (!pendingDrag || !interactionMoved) return;

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement;
    if (!target) return;
    const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;

    if (!slotCell) return;

    const newDateStr = slotCell.getAttribute('data-date') || '';
    const newMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);

    const zone = getCalendarTimeZone(pendingDrag.root);
    const newStart = DateTime.fromISO(newDateStr, { zone }).set({
        hour: Math.floor(newMinute / 60),
        minute: newMinute % 60,
        second: 0, millisecond: 0,
    });
    const newEnd = newStart.plus({ minutes: pendingDrag.durationMin });

    const newStartIso = newStart.toFormat("yyyy-MM-dd'T'HH:mm:ss");
    const newEndIso = newEnd.toFormat("yyyy-MM-dd'T'HH:mm:ss");

    const root = pendingDrag.root;
    const ctx = rootContexts.get(root);
    if (!ctx) return;

    const rangeStart = ctx.days[0].toFormat('yyyy-MM-dd');
    const rangeEnd = ctx.days[ctx.days.length - 1].plus({ days: 1 }).toFormat('yyyy-MM-dd');

    const $wire = getWire(root);
    if ($wire) {
        const token = getRenderToken(root);
        $wire.$call('eventDrop', pendingDrag.eventId, newStartIso, newEndIso, rangeStart, rangeEnd)
            .then((result) => {
                if (token !== getRenderToken(root)) return;
                const rangeStartDt = ctx.days[0].setZone(zone).startOf('day');
                const rangeEndDt = ctx.days[ctx.days.length - 1].setZone(zone).startOf('day').plus({ days: 1 });
                const expanded = expandRecurringEvents(result as CalendarEventData[], rangeStartDt, rangeEndDt, zone);
                renderTimeGridEvents(root, ctx.eventsLayer, ctx.alldayRow, expanded, ctx.days, zone);
            });
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

    if (!interactionMoved) {
        interactionMoved = true;
        pendingResize.eventEl.classList.add('lec-event--resizing');
    }

    const target = document.elementFromPoint(e.clientX, e.clientY) as HTMLElement;
    if (!target) return;
    const slotCell = target.closest('.lec-slot-cell[data-date]') as HTMLElement;
    if (!slotCell) return;

    const targetDate = slotCell.getAttribute('data-date') || '';
    if (targetDate !== pendingResize.dateStr) return;

    const targetMinute = parseInt(slotCell.getAttribute('data-minute') || '0', 10);
    const newEndMin = targetMinute + 30;

    if (newEndMin <= pendingResize.startMin) return;

    const topPercent = (pendingResize.startMin / 1440) * 100;
    const heightPercent = ((newEndMin - pendingResize.startMin) / 1440) * 100;
    pendingResize.eventEl.style.height = `${heightPercent}%`;
    pendingResize.eventEl.style.top = `${topPercent}%`;
    pendingResize.endMin = newEndMin;
}

function handleResizeEnd(): void {
    if (!pendingResize || !interactionMoved) return;

    pendingResize.eventEl.classList.remove('lec-event--resizing');

    const root = pendingResize.root;
    const ctx = rootContexts.get(root);
    if (!ctx) return;

    const zone = getCalendarTimeZone(root);
    const dayStart = DateTime.fromISO(pendingResize.dateStr, { zone });
    const newEnd = dayStart.set({
        hour: Math.floor(pendingResize.endMin / 60),
        minute: pendingResize.endMin % 60,
        second: 0, millisecond: 0,
    });
    const newEndIso = newEnd.toFormat("yyyy-MM-dd'T'HH:mm:ss");

    const rangeStart = ctx.days[0].toFormat('yyyy-MM-dd');
    const rangeEnd = ctx.days[ctx.days.length - 1].plus({ days: 1 }).toFormat('yyyy-MM-dd');

    const $wire = getWire(root);
    if ($wire) {
        const token = getRenderToken(root);
        $wire.$call('eventResize', pendingResize.eventId, pendingResize.startIso, newEndIso, rangeStart, rangeEnd)
            .then((result) => {
                if (token !== getRenderToken(root)) return;
                const rangeStartDt = ctx.days[0].setZone(zone).startOf('day');
                const rangeEndDt = ctx.days[ctx.days.length - 1].setZone(zone).startOf('day').plus({ days: 1 });
                const expanded = expandRecurringEvents(result as CalendarEventData[], rangeStartDt, rangeEndDt, zone);
                renderTimeGridEvents(root, ctx.eventsLayer, ctx.alldayRow, expanded, ctx.days, zone);
            });
    }
}

document.addEventListener('mousedown', (e) => {
    const target = e.target as HTMLElement;
    if (!target) return;

    if (target.classList.contains('lec-resize-handle')) {
        e.preventDefault();
        startResize(e, target);
        return;
    }

    const timedEvent = target.closest('.lec-timed-event') as HTMLElement;
    if (timedEvent) {
        e.preventDefault();
        startDrag(e, timedEvent);
        return;
    }

    if (target.classList.contains('lec-slot-cell') && target.hasAttribute('data-date')) {
        e.preventDefault();
        startSelect(e, target);
        return;
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
        handleEventClick(timedEvent);
    }
});

function initializeRoots(): void {
    document
        .querySelectorAll<HTMLElement>(ROOT_SELECTOR)
        .forEach((root) => {
            if (root.getAttribute(INITIALIZED_ATTR) === 'true') {
                return;
            }
            root.setAttribute(INITIALIZED_ATTR, 'true');

            const initialDateStr = root.getAttribute('data-livewire-calendar-initial-date');
            const firstDayStr = root.getAttribute('data-livewire-calendar-first-day');
            const viewAttr = root.getAttribute('data-livewire-calendar-view');
            const zone = getCalendarTimeZone(root);

            if (!initialDateStr) {
                return;
            }

            const firstDay = firstDayStr ? parseInt(firstDayStr, 10) : 0;
            const view = viewAttr || 'month';
            const initialDate = DateTime.fromISO(initialDateStr, { zone });

            let anchorDate: DateTime;
            if (view === 'timeGridDay' || view === 'resourceTimelineDay') {
                anchorDate = initialDate.startOf('day');
            } else if (view === 'timeGridWeek' || view === 'listWeek') {
                anchorDate = computeWeekStart(initialDate, firstDay);
            } else if (view === 'multiMonthYear') {
                anchorDate = initialDate.startOf('year');
            } else {
                anchorDate = initialDate.startOf('month');
            }

            renderCalendar(root, anchorDate, firstDay, view);
        });
}

initializeRoots();

document.addEventListener('livewire:navigated', initializeRoots);

document.addEventListener('livewire:initialized', () => {
    document
        .querySelectorAll<HTMLElement>(ROOT_SELECTOR)
        .forEach((root) => {
            const view = root.getAttribute('data-livewire-calendar-view') || 'month';
            const token = getRenderToken(root);

            if (view === 'month') {
                const grid = root.querySelector<HTMLElement>('[data-testid="month-grid"]');
                const firstCell = root.querySelector<HTMLElement>('[data-date]');
                if (!grid || !firstCell) return;

                const gridStartStr = firstCell.getAttribute('data-date');
                if (!gridStartStr) return;

                const zone = getCalendarTimeZone(root);
                loadEvents(root, grid, DateTime.fromISO(gridStartStr, { zone }), token);
            } else if (view === 'listWeek') {
                const listContainer = root.querySelector<HTMLElement>('[data-testid="list-view"]');
                if (!listContainer) return;

                const rangeStart = listContainer.getAttribute('data-range-start');
                if (!rangeStart) return;

                const zone = getCalendarTimeZone(root);
                loadListEvents(root, listContainer, DateTime.fromISO(rangeStart, { zone }), token);
            } else if (view === 'timeGridWeek' || view === 'timeGridDay') {
                const eventsLayer = root.querySelector<HTMLElement>('.lec-timegrid-events-layer');
                const alldayRow = root.querySelector<HTMLElement>('[data-testid="allday-row"]');
                if (!eventsLayer || !alldayRow) return;

                const zone = getCalendarTimeZone(root);

                const dayBodies = eventsLayer.querySelectorAll<HTMLElement>('[data-date]');
                const days: DateTime[] = [];
                dayBodies.forEach((body) => {
                    const dateStr = body.getAttribute('data-date');
                    if (dateStr) days.push(DateTime.fromISO(dateStr, { zone }));
                });

                if (days.length === 0) return;

                loadTimeGridEvents(root, eventsLayer, alldayRow, days, token);
            } else if (view === 'resourceTimelineDay') {
                const timeline = root.querySelector<HTMLElement>('[data-testid="resource-timeline"]');
                if (!timeline) return;

                const dateStr = timeline.getAttribute('data-date');
                if (!dateStr) return;

                const zone = getCalendarTimeZone(root);
                loadResourceTimelineResources(root, timeline, DateTime.fromISO(dateStr, { zone }), token);
            }
        });
});
