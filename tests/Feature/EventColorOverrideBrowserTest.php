<?php

use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\CalendarResource;
use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class EventColorOverrideCalendar extends LivewireCalendar
{
    protected function resources(DateRange $range): array
    {
        return [
            new CalendarResource(id: 'res-1', title: 'Room A'),
        ];
    }

    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'color-evt-1',
                title: 'Green Event',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
                extra: [
                    'backgroundColor' => '#22c55e',
                    'resourceId' => 'res-1',
                ],
            ),
        ];
    }
}

class InvalidEventColorOverrideCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'bad-color-evt',
                title: 'Bad Color',
                start: Carbon::parse('2026-05-14T09:00:00Z'),
                end: Carbon::parse('2026-05-14T10:00:00Z'),
                extra: [
                    'backgroundColor' => 'not-a-hex-color',
                ],
            ),
        ];
    }
}

beforeEach(function (): void {
    Livewire::component('event-color-override-calendar', EventColorOverrideCalendar::class);
    Livewire::component('invalid-event-color-override-calendar', InvalidEventColorOverrideCalendar::class);

    Route::get('/test-event-color-override-timegrid', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:event-color-override-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-event-color-override-month', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:event-color-override-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-event-color-override-list', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:event-color-override-calendar view="listWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-event-color-override-resource-timeline', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:event-color-override-calendar view="resourceTimelineDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    Route::get('/test-invalid-event-color-override', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:invalid-event-color-override-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('applies per-event backgroundColor override in timeGrid view', function (): void {
    $page = visit('/test-event-color-override-timegrid')
        ->assertPresent('[data-testid="timed-event-color-evt-1-2026-05-14"]');

    $bg = $page->script("getComputedStyle(document.querySelector('[data-testid=\"timed-event-color-evt-1-2026-05-14\"]')).backgroundColor");
    expect($bg)->toBe('rgb(34, 197, 94)');
});

it('applies per-event backgroundColor override in month view', function (): void {
    $page = visit('/test-event-color-override-month')
        ->assertPresent('[data-testid="month-event-color-evt-1-2026-05-14"]');

    $bg = $page->script("getComputedStyle(document.querySelector('[data-testid=\"month-event-color-evt-1-2026-05-14\"]')).backgroundColor");
    expect($bg)->toBe('rgb(34, 197, 94)');
});

it('applies per-event backgroundColor override in list view', function (): void {
    $page = visit('/test-event-color-override-list')
        ->assertPresent('[data-testid="list-event-color-evt-1"]');

    $border = $page->script("getComputedStyle(document.querySelector('[data-testid=\"list-event-color-evt-1\"]')).borderLeftColor");
    expect($border)->toBe('rgb(34, 197, 94)');
});

it('applies per-event backgroundColor override in resource timeline view', function (): void {
    $page = visit('/test-event-color-override-resource-timeline')
        ->assertPresent('[data-testid="resource-event-color-evt-1-res-1"]');

    $bg = $page->script("getComputedStyle(document.querySelector('[data-testid=\"resource-event-color-evt-1-res-1\"]')).backgroundColor");
    expect($bg)->toBe('rgb(34, 197, 94)');
});

it('ignores invalid per-event backgroundColor and uses configured default', function (): void {
    config()->set('livewire-calendar.styles.timed_event_bg', '#ef4444');

    $page = visit('/test-invalid-event-color-override')
        ->assertPresent('[data-testid="timed-event-bad-color-evt-2026-05-14"]');

    $bg = $page->script("getComputedStyle(document.querySelector('[data-testid=\"timed-event-bad-color-evt-2026-05-14\"]')).backgroundColor");
    expect($bg)->toBe('rgb(239, 68, 68)');
});
