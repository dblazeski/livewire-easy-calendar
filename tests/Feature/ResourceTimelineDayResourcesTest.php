<?php

use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class ResourceTimelineDayResourcesCalendar extends LivewireCalendar
{
    protected function resources(DateRange $range): array
    {
        return [
            new CalendarResource(id: 'res-1', title: 'Conference Room A'),
            new CalendarResource(id: 'res-2', title: 'Conference Room B'),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('resource-timeline-day-resources-calendar', ResourceTimelineDayResourcesCalendar::class);

    Route::get('/test-resource-timeline-day-resources', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:resource-timeline-day-resources-calendar
                view="resourceTimelineDay"
                initial-date="2026-05-14"
                first-day="0"
                today="2026-01-15"
                time-zone="America/New_York"
            />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders the resource timeline day skeleton and loads resources', function (): void {
    visit('/test-resource-timeline-day-resources')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="calendar-title"]')

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
