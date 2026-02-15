<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class TimeGridAllDayEventsCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'ad-evt-1',
                title: 'Company Retreat',
                start: Carbon::parse('2026-05-14T00:00:00Z'),
                end: Carbon::parse('2026-05-15T00:00:00Z'),
                extra: ['allDay' => true],
            ),
            new CalendarEvent(
                id: 'ad-evt-2',
                title: 'Multi-Day Conference',
                start: Carbon::parse('2026-05-13T00:00:00Z'),
                end: Carbon::parse('2026-05-16T00:00:00Z'),
                extra: ['allDay' => true],
            ),
            new CalendarEvent(
                id: 'timed-evt-1',
                title: 'Morning Meeting',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('timegrid-allday-events-calendar', TimeGridAllDayEventsCalendar::class);

    Route::get('/test-allday-week', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-allday-events-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-allday-day', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-allday-events-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders allday cells with data-testid for each day in week view', function (): void {
    visit('/test-allday-week')
        ->assertPresent('[data-testid="allday-cell-2026-05-10"]')
        ->assertPresent('[data-testid="allday-cell-2026-05-14"]')
        ->assertPresent('[data-testid="allday-cell-2026-05-16"]');
});

it('renders allday cells with data-date attribute', function (): void {
    visit('/test-allday-week')
        ->assertAttribute('[data-testid="allday-cell-2026-05-14"]', 'data-date', '2026-05-14');
});

it('renders a single-day allday event in the correct cell in week view', function (): void {
    visit('/test-allday-week')
        ->assertPresent('[data-testid="allday-event-ad-evt-1-2026-05-14"]')
        ->assertSeeIn('[data-testid="allday-cell-2026-05-14"]', 'Company Retreat');
});

it('renders a multi-day allday event across multiple cells in week view', function (): void {
    visit('/test-allday-week')
        ->assertPresent('[data-testid="allday-event-ad-evt-2-2026-05-13"]')
        ->assertPresent('[data-testid="allday-event-ad-evt-2-2026-05-14"]')
        ->assertPresent('[data-testid="allday-event-ad-evt-2-2026-05-15"]')
        ->assertNotPresent('[data-testid="allday-event-ad-evt-2-2026-05-16"]');
});

it('does not render allday events in the timed events layer', function (): void {
    visit('/test-allday-week')
        ->assertNotPresent('[data-testid="timed-event-ad-evt-1-2026-05-14"]')
        ->assertNotPresent('[data-testid="timed-event-ad-evt-2-2026-05-14"]');
});

it('renders timed events alongside allday events', function (): void {
    visit('/test-allday-week')
        ->assertPresent('[data-testid="allday-event-ad-evt-1-2026-05-14"]')
        ->assertPresent('[data-testid="timed-event-timed-evt-1-2026-05-14"]');
});

it('renders allday event in day view', function (): void {
    visit('/test-allday-day')
        ->assertPresent('[data-testid="allday-event-ad-evt-1-2026-05-14"]')
        ->assertPresent('[data-testid="allday-cell-2026-05-14"]');
});

it('renders allday cell with data-date attribute in day view', function (): void {
    visit('/test-allday-day')
        ->assertAttribute('[data-testid="allday-cell-2026-05-14"]', 'data-date', '2026-05-14');
});
