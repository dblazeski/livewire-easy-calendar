<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-timegrid-week', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('emits the view attribute on the root element', function (): void {
    visit('/test-timegrid-week')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'timeGridWeek');
});

it('renders the timegrid container', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="timegrid"]');
});

it('renders the all-day row', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="allday-row"]');
});

it('renders seven day columns for the week containing 2026-05-14 (Sunday start)', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="timegrid-day-2026-05-10"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-11"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-12"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-13"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-14"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-15"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-16"]');
});

it('renders hour time labels from 00:00 to 23:00', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="time-label-00:00"]')
        ->assertPresent('[data-testid="time-label-06:00"]')
        ->assertPresent('[data-testid="time-label-12:00"]')
        ->assertPresent('[data-testid="time-label-18:00"]')
        ->assertPresent('[data-testid="time-label-23:00"]');
});

it('reuses the same toolbar selectors as month view', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]');
});

it('navigates to the next week on btn-next click', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="timegrid-day-2026-05-10"]')
        ->click('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-17"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-18"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-19"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-20"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-21"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-22"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-23"]');
});

it('navigates to the previous week on btn-prev click', function (): void {
    visit('/test-timegrid-week')
        ->click('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-17"]')
        ->click('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-10"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-16"]');
});

it('navigates to the week containing today on btn-today click', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="timegrid-day-2026-05-10"]')
        ->click('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-11"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-12"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-13"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-14"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-15"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-16"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-17"]');
});

it('renders a calendar title', function (): void {
    visit('/test-timegrid-week')
        ->assertPresent('[data-testid="calendar-title"]');
});
