<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class InteractionTestCalendar extends LivewireCalendar
{
    public string $evt1Start = '2026-05-14T09:00:00+00:00';

    public string $evt1End = '2026-05-14T10:00:00+00:00';

    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'int-evt-1',
                title: 'Movable Meeting',
                start: Carbon::parse($this->evt1Start),
                end: Carbon::parse($this->evt1End),
            ),
            new CalendarEvent(
                id: 'int-evt-2',
                title: 'Fixed Workshop',
                start: Carbon::parse('2026-05-15T13:00:00+00:00'),
                end: Carbon::parse('2026-05-15T14:00:00+00:00'),
            ),
        ];
    }

    protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
    {
        if ($eventId === 'int-evt-1') {
            $this->evt1Start = $newStart;
            $this->evt1End = $newEnd;
        }
    }

    protected function onEventResize(string $eventId, string $newStart, string $newEnd): void
    {
        if ($eventId === 'int-evt-1') {
            $this->evt1End = $newEnd;
        }
    }
}

class ResourceTimelineInteractionTestCalendar extends LivewireCalendar
{
    public string $evt1Start = '2026-05-14T09:00:00+00:00';

    public string $evt1End = '2026-05-14T10:00:00+00:00';

    protected function resources(DateRange $range): array
    {
        return [
            new CalendarResource(id: 'res-1', title: 'Room A'),
        ];
    }

    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'res-int-evt-1',
                title: 'Movable Booking',
                start: Carbon::parse($this->evt1Start),
                end: Carbon::parse($this->evt1End),
                extra: ['resourceId' => 'res-1'],
            ),
        ];
    }

    protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
    {
        if ($eventId === 'res-int-evt-1') {
            $this->evt1Start = $newStart;
            $this->evt1End = $newEnd;
        }
    }
}

beforeEach(function (): void {
    Livewire::component('interaction-test-calendar', InteractionTestCalendar::class);
    Livewire::component('resource-timeline-interaction-test-calendar', ResourceTimelineInteractionTestCalendar::class);

    Route::get('/test-interactions', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:interaction-test-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-interactions-month', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:interaction-test-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-interactions-list', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:interaction-test-calendar view="listWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-interactions-resource-timeline', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:resource-timeline-interaction-test-calendar view="resourceTimelineDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders slot cells with stable data-testid, data-date, and data-minute attributes', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="slot-cell-2026-05-14-09:00"]')
        ->assertAttribute('[data-testid="slot-cell-2026-05-14-09:00"]', 'data-date', '2026-05-14')
        ->assertAttribute('[data-testid="slot-cell-2026-05-14-09:00"]', 'data-minute', '540');
});

it('renders resize handles on timed events', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="timed-event-resize-handle-int-evt-1-2026-05-14"]');
});

it('renders data-event-start and data-event-end on timed events', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-event-start', '2026-05-14T09:00:00+00:00')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-event-end', '2026-05-14T10:00:00+00:00');
});

it('toggles data-selected on timed event click', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->click('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-selected', 'true');
});

it('removes data-selected on second click', function (): void {
    visit('/test-interactions')
        ->click('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-selected', 'true')
        ->click('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertNotPresent('[data-testid="timed-event-int-evt-1-2026-05-14"][data-selected="true"]');
});

it('creates a selection element when clicking a slot cell', function (): void {
    $page = visit('/test-interactions')
        ->assertPresent('[data-testid="slot-cell-2026-05-14-09:00"]');

    $page->script("
        const cell = document.querySelector('[data-testid=\"slot-cell-2026-05-14-09:00\"]');
        cell.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        cell.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    ");

    $page->assertPresent('[data-testid="timegrid-selection"]')
        ->assertAttribute('[data-testid="timegrid-selection"]', 'data-start', '2026-05-14T09:00:00')
        ->assertAttribute('[data-testid="timegrid-selection"]', 'data-end', '2026-05-14T09:30:00');
});

it('clears event selection when clicking a slot cell', function (): void {
    $page = visit('/test-interactions')
        ->click('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-selected', 'true');

    $page->script("
        const cell = document.querySelector('[data-testid=\"slot-cell-2026-05-14-11:00\"]');
        cell.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        cell.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    ");

    $page->assertNotPresent('[data-testid="timed-event-int-evt-1-2026-05-14"][data-selected="true"]')
        ->assertPresent('[data-testid="timegrid-selection"]');
});

it('clears selection when clicking a timed event', function (): void {
    $page = visit('/test-interactions')
        ->assertPresent('[data-testid="slot-cell-2026-05-14-11:00"]');

    $page->script("
        const cell = document.querySelector('[data-testid=\"slot-cell-2026-05-14-11:00\"]');
        cell.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        cell.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
    ");

    $page->assertPresent('[data-testid="timegrid-selection"]')
        ->click('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertNotPresent('[data-testid="timegrid-selection"]');
});

it('moves event to a new slot on drag and drop', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->drag(
            '[data-testid="timed-event-int-evt-1-2026-05-14"]',
            '[data-testid="slot-cell-2026-05-15-10:00"]',
        )
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-15"]');
});

it('resizes event to a new end time via resize handle', function (): void {
    visit('/test-interactions')
        ->assertPresent('[data-testid="timed-event-resize-handle-int-evt-1-2026-05-14"]')
        ->drag(
            '[data-testid="timed-event-resize-handle-int-evt-1-2026-05-14"]',
            '[data-testid="slot-cell-2026-05-14-11:00"]',
        )
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-int-evt-1-2026-05-14"]', 'data-end-min', '690');
});

it('moves event to a new day on drag and drop in month view', function (): void {
    visit('/test-interactions-month')
        ->assertPresent('[data-testid="month-event-int-evt-1-2026-05-14"]')
        ->drag(
            '[data-testid="month-event-int-evt-1-2026-05-14"]',
            '[data-testid="day-cell-2026-05-15"]',
        )
        ->assertPresent('[data-testid="month-event-int-evt-1-2026-05-15"]');
});

it('moves event to a new day on drag and drop in list view', function (): void {
    visit('/test-interactions-list')
        ->assertPresent('[data-testid="list-event-int-evt-1"]')
        ->assertPresent('[data-testid="list-day-2026-05-15"]')
        ->drag(
            '[data-testid="list-event-int-evt-1"]',
            '[data-testid="list-day-2026-05-15"]',
        )
        ->assertPresent('[data-testid="list-day-2026-05-15"] [data-testid="list-event-int-evt-1"]');
});

it('moves resource timeline event to a new time on drag and drop', function (): void {
    visit('/test-interactions-resource-timeline')
        ->assertPresent('[data-testid="resource-event-res-int-evt-1-res-1"]')
        ->assertAttribute('[data-testid="resource-event-res-int-evt-1-res-1"]', 'data-start-min', '540')
        ->drag(
            '[data-testid="resource-event-res-int-evt-1-res-1"]',
            '[data-testid="resource-axis-tick-12:00"]',
        )
        ->assertAttribute('[data-testid="resource-event-res-int-evt-1-res-1"]', 'data-start-min', '720');
});

it('applies configured event colors via CSS variables', function (): void {
    config()->set('livewire-calendar.styles.timed_event_bg', '#ef4444');

    $page = visit('/test-interactions')
        ->assertPresent('[data-livewire-calendar-root]')
        ->assertPresent('[data-testid="timed-event-int-evt-1-2026-05-14"]');

    $var = $page->script("getComputedStyle(document.querySelector('[data-livewire-calendar-root]')).getPropertyValue('--lec-timed-event-bg').trim()");
    expect($var)->toBe('#ef4444');

    $bg = $page->script("getComputedStyle(document.querySelector('[data-testid=\"timed-event-int-evt-1-2026-05-14\"]')).backgroundColor");
    expect($bg)->toBe('rgb(239, 68, 68)');
});
