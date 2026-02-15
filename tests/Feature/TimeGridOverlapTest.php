<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class TimeGridOverlapCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'ol-evt-1',
                title: 'Meeting A',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
            ),
            new CalendarEvent(
                id: 'ol-evt-2',
                title: 'Meeting B',
                start: Carbon::parse('2026-05-14T09:30:00Z'),
                end: Carbon::parse('2026-05-14T10:30:00Z'),
            ),
            new CalendarEvent(
                id: 'ol-evt-3',
                title: 'Meeting C',
                start: Carbon::parse('2026-05-14T14:00:00Z'),
                end: Carbon::parse('2026-05-14T15:00:00Z'),
            ),
        ];
    }
}

class TimeGridTripleOverlapCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'triple-1',
                title: 'Event A',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
            ),
            new CalendarEvent(
                id: 'triple-2',
                title: 'Event B',
                start: Carbon::parse('2026-05-14T09:15:00Z'),
                end: Carbon::parse('2026-05-14T10:15:00Z'),
            ),
            new CalendarEvent(
                id: 'triple-3',
                title: 'Event C',
                start: Carbon::parse('2026-05-14T09:30:00Z'),
                end: Carbon::parse('2026-05-14T10:30:00Z'),
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('timegrid-overlap-calendar', TimeGridOverlapCalendar::class);
    Livewire::component('timegrid-triple-overlap-calendar', TimeGridTripleOverlapCalendar::class);

    Route::get('/test-overlap-week', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-overlap-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-triple-overlap-day', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:timegrid-triple-overlap-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('assigns overlapping events to full width', function (): void {
    visit('/test-overlap-week')
        ->assertPresent('[data-testid="timed-event-ol-evt-1-2026-05-14"]')
        ->assertPresent('[data-testid="timed-event-ol-evt-2-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-ol-evt-1-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-ol-evt-2-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-ol-evt-1-2026-05-14"]', 'data-col-count', '1')
        ->assertAttribute('[data-testid="timed-event-ol-evt-2-2026-05-14"]', 'data-col-count', '1');
});

it('assigns non-overlapping event to its own column', function (): void {
    visit('/test-overlap-week')
        ->assertPresent('[data-testid="timed-event-ol-evt-3-2026-05-14"]')
        ->assertAttribute('[data-testid="timed-event-ol-evt-3-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-ol-evt-3-2026-05-14"]', 'data-col-count', '1');
});

it('assigns three overlapping events to full width in day view', function (): void {
    visit('/test-triple-overlap-day')
        ->assertAttribute('[data-testid="timed-event-triple-1-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-triple-2-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-triple-3-2026-05-14"]', 'data-col', '0')
        ->assertAttribute('[data-testid="timed-event-triple-1-2026-05-14"]', 'data-col-count', '1')
        ->assertAttribute('[data-testid="timed-event-triple-2-2026-05-14"]', 'data-col-count', '1')
        ->assertAttribute('[data-testid="timed-event-triple-3-2026-05-14"]', 'data-col-count', '1');
});

it('preserves data-start-min and data-end-min with overlap layout', function (): void {
    visit('/test-overlap-week')
        ->assertAttribute('[data-testid="timed-event-ol-evt-1-2026-05-14"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="timed-event-ol-evt-1-2026-05-14"]', 'data-end-min', '600')
        ->assertAttribute('[data-testid="timed-event-ol-evt-2-2026-05-14"]', 'data-start-min', '570')
        ->assertAttribute('[data-testid="timed-event-ol-evt-2-2026-05-14"]', 'data-end-min', '630');
});

it('renders non-overlapping events with full width column count', function (): void {
    visit('/test-overlap-week')
        ->assertAttribute('[data-testid="timed-event-ol-evt-3-2026-05-14"]', 'data-col-count', '1');
});
