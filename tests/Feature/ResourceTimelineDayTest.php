<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class ResourceTimelineDayCalendar extends LivewireCalendar
{
    protected function resources(DateRange $range): array
    {
        return [
            new CalendarResource(id: 'res-1', title: 'Conference Room A'),
            new CalendarResource(id: 'res-2', title: 'Conference Room B'),
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
            new CalendarEvent(
                id: 'rec-evt',
                title: 'Daily room A',
                start: Carbon::parse('2026-05-14T10:00:00Z'),
                end: Carbon::parse('2026-05-14T10:30:00Z'),
                extra: [
                    'resourceId' => 'res-1',
                    'rrule' => 'FREQ=DAILY;COUNT=2',
                ],
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('resource-timeline-day-calendar', ResourceTimelineDayCalendar::class);

    Route::get('/test-resource-timeline-day', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:resource-timeline-day-calendar view="resourceTimelineDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('emits the view attribute on the root element', function (): void {
    visit('/test-resource-timeline-day')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'resourceTimelineDay');
});

it('renders resources with stable row + label selectors', function (): void {
    visit('/test-resource-timeline-day')
        ->assertPresent('[data-testid="resource-timeline"]')
        ->assertPresent('[data-testid="resource-row-res-1"]')
        ->assertAttribute('[data-testid="resource-row-res-1"]', 'data-resource-id', 'res-1')
        ->assertPresent('[data-testid="resource-label-res-1"]')
        ->assertSeeIn('[data-testid="resource-label-res-1"]', 'Conference Room A')
        ->assertPresent('[data-testid="resource-row-res-2"]')
        ->assertAttribute('[data-testid="resource-row-res-2"]', 'data-resource-id', 'res-2')
        ->assertPresent('[data-testid="resource-label-res-2"]')
        ->assertSeeIn('[data-testid="resource-label-res-2"]', 'Conference Room B');
});

it('renders fixed events in the correct resource rows with minute attributes', function (): void {
    visit('/test-resource-timeline-day')
        ->assertPresent('[data-testid="resource-row-res-1"] [data-testid="resource-event-evt-1-res-1"]')
        ->assertAttribute('[data-testid="resource-event-evt-1-res-1"]', 'data-resource-id', 'res-1')
        ->assertAttribute('[data-testid="resource-event-evt-1-res-1"]', 'data-start-min', '540')
        ->assertAttribute('[data-testid="resource-event-evt-1-res-1"]', 'data-end-min', '600')
        ->assertPresent('[data-testid="resource-row-res-2"] [data-testid="resource-event-evt-2-res-2"]')
        ->assertAttribute('[data-testid="resource-event-evt-2-res-2"]', 'data-resource-id', 'res-2')
        ->assertAttribute('[data-testid="resource-event-evt-2-res-2"]', 'data-start-min', '690')
        ->assertAttribute('[data-testid="resource-event-evt-2-res-2"]', 'data-end-min', '720');
});

it('renders recurring resource events on the current day and after next-day navigation', function (): void {
    visit('/test-resource-timeline-day')
        ->assertPresent('[data-testid="resource-event-rec-evt__20260514T100000-res-1"]')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260514T100000-res-1"]', 'data-resource-id', 'res-1')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260514T100000-res-1"]', 'data-start-min', '600')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260514T100000-res-1"]', 'data-end-min', '630')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 15, 2026')
        ->assertNotPresent('[data-testid="resource-event-evt-1-res-1"]')
        ->assertNotPresent('[data-testid="resource-event-evt-2-res-2"]')
        ->assertPresent('[data-testid="resource-event-rec-evt__20260515T100000-res-1"]')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260515T100000-res-1"]', 'data-resource-id', 'res-1')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260515T100000-res-1"]', 'data-start-min', '600')
        ->assertAttribute('[data-testid="resource-event-rec-evt__20260515T100000-res-1"]', 'data-end-min', '630');
});
