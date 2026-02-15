<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class RecurrenceBoundaryCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'bnd-evt',
                title: 'Boundary Recurrence',
                start: Carbon::parse('2026-04-26T10:00:00Z'),
                end: Carbon::parse('2026-04-26T11:00:00Z'),
                extra: [
                    'rrule' => 'FREQ=DAILY;COUNT=2',
                ],
            ),
        ];
    }
}

class RecurrenceExdateCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'ex-evt',
                title: 'Exdate Recurrence',
                start: Carbon::parse('2026-05-14T10:00:00Z'),
                end: Carbon::parse('2026-05-14T11:00:00Z'),
                extra: [
                    'rrule' => 'FREQ=DAILY;COUNT=3',
                    'exdate' => ['2026-05-15'],
                ],
            ),
        ];
    }
}

class DstRecurrenceCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        $start = Carbon::create(2026, 3, 7, 9, 0, 0, 'America/New_York');

        return [
            new CalendarEvent(
                id: 'dst-evt',
                title: 'DST Daily',
                start: $start,
                end: $start->copy()->addHour(),
                extra: [
                    'rrule' => 'FREQ=DAILY;COUNT=3',
                ],
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('recurrence-boundary-calendar', RecurrenceBoundaryCalendar::class);
    Livewire::component('recurrence-exdate-calendar', RecurrenceExdateCalendar::class);
    Livewire::component('dst-recurrence-calendar', DstRecurrenceCalendar::class);

    Route::get('/test-recurrence-boundary', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:recurrence-boundary-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-recurrence-exdate', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:recurrence-exdate-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-recurrence-dst', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:dst-recurrence-calendar view="timeGridWeek" initial-date="2026-03-08" first-day="6" today="2026-01-15" time-zone="America/New_York" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('expands RRULE occurrences in month view starting at the grid start', function (): void {
    visit('/test-recurrence-boundary')
        ->assertSeeIn('[data-testid="day-cell-2026-04-26"]', 'Boundary Recurrence')
        ->assertSeeIn('[data-testid="day-cell-2026-04-27"]', 'Boundary Recurrence')
        ->assertNotPresent('[data-event-id="bnd-evt__20260428T100000"]');
});

it('excludes EXDATE occurrences from the expanded set', function (): void {
    visit('/test-recurrence-exdate')
        ->assertPresent('[data-event-id="ex-evt__20260514T100000"]')
        ->assertPresent('[data-event-id="ex-evt__20260516T100000"]')
        ->assertNotPresent('[data-event-id="ex-evt__20260515T100000"]');
});

it('renders DST-crossing daily recurrences at the correct wall time slot', function (): void {
    visit('/test-recurrence-dst')
        ->assertPresent('[data-testid="timed-event-dst-evt__20260307T090000-2026-03-07"]')
        ->assertAttribute('[data-testid="timed-event-dst-evt__20260307T090000-2026-03-07"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="timed-event-dst-evt__20260307T090000-2026-03-07"]', 'data-end-min', '600')
        ->assertPresent('[data-testid="timed-event-dst-evt__20260308T090000-2026-03-08"]')
        ->assertAttribute('[data-testid="timed-event-dst-evt__20260308T090000-2026-03-08"]', 'data-start-min', '540')
        ->assertPresent('[data-testid="timed-event-dst-evt__20260309T090000-2026-03-09"]')
        ->assertAttribute('[data-testid="timed-event-dst-evt__20260309T090000-2026-03-09"]', 'data-start-min', '540');
});
