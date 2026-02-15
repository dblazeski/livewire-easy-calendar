<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class EventRenderingTestCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'evt-render-1',
                title: 'Board Meeting',
                start: Carbon::parse('2026-05-15T10:00:00Z'),
                end: Carbon::parse('2026-05-15T11:00:00Z'),
            ),
            new CalendarEvent(
                id: 'evt-render-2',
                title: 'Lunch Break',
                start: Carbon::parse('2026-05-20T12:00:00Z'),
                end: Carbon::parse('2026-05-20T13:00:00Z'),
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('event-rendering-test-calendar', EventRenderingTestCalendar::class);

    Route::get('/test-event-rendering', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:event-rendering-test-calendar initial-date="2026-05-01" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders an event inside the correct day cell', function (): void {
    visit('/test-event-rendering')
        ->assertPresent('[data-event-id="evt-render-1"]')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', 'Board Meeting');
});

it('renders multiple events in their respective day cells', function (): void {
    visit('/test-event-rendering')
        ->assertPresent('[data-event-id="evt-render-1"]')
        ->assertPresent('[data-event-id="evt-render-2"]')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', 'Board Meeting')
        ->assertSeeIn('[data-testid="day-cell-2026-05-20"]', 'Lunch Break');
});

it('preserves the day number alongside events', function (): void {
    visit('/test-event-rendering')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', '15')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', 'Board Meeting');
});

it('re-fetches events after navigating away and back', function (): void {
    visit('/test-event-rendering')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', 'Board Meeting')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'June 2026')
        ->click('[data-testid="btn-prev"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 2026')
        ->assertSeeIn('[data-testid="day-cell-2026-05-15"]', 'Board Meeting');
});
