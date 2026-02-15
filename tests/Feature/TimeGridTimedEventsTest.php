<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class TimeGridTimedEventsCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'tg-evt-1',
                title: 'Morning Standup',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T09:30:00Z'),
            ),
            new CalendarEvent(
                id: 'tg-evt-2',
                title: 'Team Lunch',
                start: Carbon::parse('2026-05-15T12:00:00Z'),
                end: Carbon::parse('2026-05-15T13:00:00Z'),
            ),
            new CalendarEvent(
                id: 'tg-evt-3',
                title: 'Overnight Deploy',
                start: Carbon::parse('2026-05-13T23:00:00Z'),
                end: Carbon::parse('2026-05-14T01:00:00Z'),
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('timegrid-timed-events-calendar', TimeGridTimedEventsCalendar::class);

    Route::get('/test-timegrid-week-events', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-timed-events-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-timegrid-day-events', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-timed-events-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders day-body containers for each day in the week', function (): void {
    visit('/test-timegrid-week-events')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-10"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-11"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-12"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-13"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-14"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-15"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-16"]');
});

it('renders a timed event with correct data attributes in week view', function (): void {
    visit('/test-timegrid-week-events')
        ->assertPresent('[data-testid="timed-event-tg-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-event-id', 'tg-evt-1')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-date', '2026-05-14')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-end-min', '570');
});

it('renders event title text in week view', function (): void {
    visit('/test-timegrid-week-events')
        ->assertSeeIn('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'Morning Standup');
});

it('renders events on different days in their respective day bodies', function (): void {
    visit('/test-timegrid-week-events')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-14"] [data-testid="timed-event-tg-evt-1-2026-05-14"]')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-15"] [data-testid="timed-event-tg-evt-2-2026-05-15"]');
});

it('splits midnight-spanning event into two day segments', function (): void {
    visit('/test-timegrid-week-events')
        ->assertPresent('[data-testid="timed-event-tg-evt-3-2026-05-13"]')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-13"]', 'data-start-min', '1380')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-13"]', 'data-end-min', '1440')
        ->assertPresent('[data-testid="timed-event-tg-evt-3-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-14"]', 'data-start-min', '0')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-14"]', 'data-end-min', '60');
});

it('re-fetches events after navigating in week view', function (): void {
    visit('/test-timegrid-week-events')
        ->assertPresent('[data-testid="timed-event-tg-evt-1-2026-05-14"]')
        ->click('[data-testid="btn-next"]')
        ->assertNotPresent('[data-testid="timed-event-tg-evt-1-2026-05-14"]')
        ->click('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="timed-event-tg-evt-1-2026-05-14"]');
});

it('renders a day-body container in day view', function (): void {
    visit('/test-timegrid-day-events')
        ->assertPresent('[data-testid="timegrid-day-body-2026-05-14"]');
});

it('renders a timed event with correct data attributes in day view', function (): void {
    visit('/test-timegrid-day-events')
        ->assertPresent('[data-testid="timed-event-tg-evt-1-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="timed-event-tg-evt-1-2026-05-14"]', 'data-end-min', '570');
});

it('renders midnight-spanning segment for anchor day only in day view', function (): void {
    visit('/test-timegrid-day-events')
        ->assertPresent('[data-testid="timed-event-tg-evt-3-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-14"]', 'data-start-min', '0')
        ->assertAttribute('[data-testid="timed-event-tg-evt-3-2026-05-14"]', 'data-end-min', '60')
        ->assertNotPresent('[data-testid="timed-event-tg-evt-3-2026-05-13"]');
});
