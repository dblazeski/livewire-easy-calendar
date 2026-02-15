<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class ListViewTestCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'list-evt-2',
                title: 'Afternoon Workshop',
                start: Carbon::parse('2026-05-14T14:00:00Z'),
                end: Carbon::parse('2026-05-14T16:00:00Z'),
            ),
            new CalendarEvent(
                id: 'list-evt-1',
                title: 'Morning Standup',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T09:30:00Z'),
            ),
            new CalendarEvent(
                id: 'list-evt-3',
                title: 'Team Lunch',
                start: Carbon::parse('2026-05-15T12:00:00Z'),
                end: Carbon::parse('2026-05-15T13:00:00Z'),
            ),
            new CalendarEvent(
                id: 'list-evt-4',
                title: 'All Day Offsite',
                start: Carbon::parse('2026-05-13T00:00:00Z'),
                end: Carbon::parse('2026-05-14T00:00:00Z'),
                extra: ['allDay' => true],
            ),
        ];
    }
}

class ListViewSortingCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'sort-c',
                title: 'Event C',
                start: Carbon::parse('2026-05-14T10:00:00Z'),
                end: Carbon::parse('2026-05-14T11:00:00Z'),
            ),
            new CalendarEvent(
                id: 'sort-a',
                title: 'Event A',
                start: Carbon::parse('2026-05-14T10:00:00Z'),
                end: Carbon::parse('2026-05-14T11:00:00Z'),
            ),
            new CalendarEvent(
                id: 'sort-b',
                title: 'Event B',
                start: Carbon::parse('2026-05-14T10:00:00Z'),
                end: Carbon::parse('2026-05-14T10:30:00Z'),
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('list-view-test-calendar', ListViewTestCalendar::class);
    Livewire::component('list-view-sorting-calendar', ListViewSortingCalendar::class);

    Route::get('/test-list-view', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:list-view-test-calendar view="listWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-list-view-sorting', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:list-view-sorting-calendar view="listWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders the list-view container', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-view"]');
});

it('emits the listWeek view attribute on the root element', function (): void {
    visit('/test-list-view')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'listWeek');
});

it('renders a calendar title with the week range', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="calendar-title"]');
});

it('renders the standard toolbar buttons', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]');
});

it('renders events grouped by day with correct testids', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-day-2026-05-13"]')
        ->assertPresent('[data-testid="list-day-2026-05-14"]')
        ->assertPresent('[data-testid="list-day-2026-05-15"]');
});

it('renders individual event rows with correct selectors', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-event-list-evt-1"]')
        ->assertAttribute('[data-testid="list-event-list-evt-1"]', 'data-event-id', 'list-evt-1')
        ->assertAttribute('[data-testid="list-event-list-evt-1"]', 'data-date', '2026-05-14');
});

it('displays event titles in the list', function (): void {
    visit('/test-list-view')
        ->assertSeeIn('[data-testid="list-event-list-evt-1"]', 'Morning Standup')
        ->assertSeeIn('[data-testid="list-event-list-evt-2"]', 'Afternoon Workshop')
        ->assertSeeIn('[data-testid="list-event-list-evt-3"]', 'Team Lunch');
});

it('displays time range for timed events', function (): void {
    visit('/test-list-view')
        ->assertSeeIn('[data-testid="list-event-list-evt-1"]', '09:00')
        ->assertSeeIn('[data-testid="list-event-list-evt-1"]', '09:30');
});

it('displays all-day label for all-day events', function (): void {
    visit('/test-list-view')
        ->assertSeeIn('[data-testid="list-event-list-evt-4"]', 'all-day');
});

it('sorts events by start asc, end asc, then id asc', function (): void {
    visit('/test-list-view-sorting')
        ->assertPresent('[data-testid="list-day-2026-05-14"]')
        ->assertPresent('[data-testid="list-day-2026-05-14"] .lec-list-event:nth-child(2)[data-event-id="sort-b"]')
        ->assertPresent('[data-testid="list-day-2026-05-14"] .lec-list-event:nth-child(3)[data-event-id="sort-a"]')
        ->assertPresent('[data-testid="list-day-2026-05-14"] .lec-list-event:nth-child(4)[data-event-id="sort-c"]');
});

it('navigates to the next week on btn-next click', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-event-list-evt-1"]')
        ->click('[data-testid="btn-next"]')
        ->assertNotPresent('[data-testid="list-event-list-evt-1"]');
});

it('navigates to the previous week on btn-prev click', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-event-list-evt-1"]')
        ->click('[data-testid="btn-next"]')
        ->assertNotPresent('[data-testid="list-event-list-evt-1"]')
        ->click('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="list-event-list-evt-1"]');
});

it('navigates to the week containing today on btn-today click', function (): void {
    visit('/test-list-view')
        ->assertPresent('[data-testid="list-event-list-evt-1"]')
        ->click('[data-testid="btn-today"]')
        ->assertNotPresent('[data-testid="list-event-list-evt-1"]');
});
