<?php

use Calendar\LivewireCalendar\CalendarEvent;
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

beforeEach(function (): void {
    Livewire::component('interaction-test-calendar', InteractionTestCalendar::class);

    Route::get('/test-interactions', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:interaction-test-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
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
