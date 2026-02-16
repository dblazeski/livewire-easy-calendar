<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class ResourceTimeGridDayCalendar extends LivewireCalendar
{
    protected function resources(DateRange $range): array
    {
        return [
            new CalendarResource(id: 'res-1', title: 'Room A'),
            new CalendarResource(id: 'res-2', title: 'Room B'),
        ];
    }

    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'evt-1',
                title: 'Room A booking',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
                extra: ['resourceId' => 'res-1'],
            ),
            new CalendarEvent(
                id: 'evt-2',
                title: 'Room B booking',
                start: Carbon::parse('2026-05-14T11:30:00Z'),
                end: Carbon::parse('2026-05-14T12:00:00Z'),
                extra: ['resourceId' => 'res-2'],
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('resource-timegrid-day-calendar', ResourceTimeGridDayCalendar::class);

    Route::get('/test-resource-timegrid-day', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:resource-timegrid-day-calendar view="resourceTimeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('emits the view attribute on the root element', function (): void {
    visit('/test-resource-timegrid-day')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'resourceTimeGridDay');
});

it('renders resources as vertical columns with stable selectors', function (): void {
    visit('/test-resource-timegrid-day')
        ->assertPresent('[data-testid="resource-timegrid"]')
        ->assertPresent('[data-testid="resource-timegrid-header-res-1"]')
        ->assertAttribute('[data-testid="resource-timegrid-header-res-1"]', 'data-resource-id', 'res-1')
        ->assertSeeIn('[data-testid="resource-timegrid-header-res-1"]', 'Room A')
        ->assertPresent('[data-testid="resource-timegrid-header-res-2"]')
        ->assertAttribute('[data-testid="resource-timegrid-header-res-2"]', 'data-resource-id', 'res-2')
        ->assertSeeIn('[data-testid="resource-timegrid-header-res-2"]', 'Room B');
});

it('renders slot cells per resource with minute attributes', function (): void {
    visit('/test-resource-timegrid-day')
        ->assertPresent('[data-testid="resource-slot-cell-res-1-2026-05-14-09:00"]')
        ->assertAttribute('[data-testid="resource-slot-cell-res-1-2026-05-14-09:00"]', 'data-resource-id', 'res-1')
        ->assertAttribute('[data-testid="resource-slot-cell-res-1-2026-05-14-09:00"]', 'data-date', '2026-05-14')
        ->assertAttribute('[data-testid="resource-slot-cell-res-1-2026-05-14-09:00"]', 'data-minute', '540');
});

it('renders timed events within the correct resource columns with minute attributes', function (): void {
    visit('/test-resource-timegrid-day')
        ->assertPresent('[data-testid="resource-timegrid-body-res-1"] [data-testid="resource-timed-event-evt-1-res-1-2026-05-14"]')
        ->assertAttribute('[data-testid="resource-timed-event-evt-1-res-1-2026-05-14"]', 'data-resource-id', 'res-1')
        ->assertAttribute('[data-testid="resource-timed-event-evt-1-res-1-2026-05-14"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="resource-timed-event-evt-1-res-1-2026-05-14"]', 'data-end-min', '600')
        ->assertPresent('[data-testid="resource-timegrid-body-res-2"] [data-testid="resource-timed-event-evt-2-res-2-2026-05-14"]')
        ->assertAttribute('[data-testid="resource-timed-event-evt-2-res-2-2026-05-14"]', 'data-resource-id', 'res-2')
        ->assertAttribute('[data-testid="resource-timed-event-evt-2-res-2-2026-05-14"]', 'data-start-min', '690')
        ->assertAttribute('[data-testid="resource-timed-event-evt-2-res-2-2026-05-14"]', 'data-end-min', '720');
});

it('navigates by day using the standard toolbar buttons', function (): void {
    visit('/test-resource-timegrid-day')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 14, 2026')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 15, 2026')
        ->assertNotPresent('[data-testid="resource-timed-event-evt-1-res-1-2026-05-14"]');
});
