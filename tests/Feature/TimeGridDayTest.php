<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-timegrid-day', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('emits the view attribute on the root element', function (): void {
    visit('/test-timegrid-day')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'timeGridDay');
});

it('renders the timegrid container', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="timegrid"]');
});

it('renders the all-day row', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="allday-row"]');
});

it('renders a single day column for 2026-05-14', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="timegrid-day-2026-05-14"]');
});

it('renders hour time labels from 00:00 to 23:00', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="time-label-00:00"]')
        ->assertPresent('[data-testid="time-label-06:00"]')
        ->assertPresent('[data-testid="time-label-12:00"]')
        ->assertPresent('[data-testid="time-label-18:00"]')
        ->assertPresent('[data-testid="time-label-23:00"]');
});

it('reuses the same toolbar selectors', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]');
});

it('navigates to the next day on btn-next click', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="timegrid-day-2026-05-14"]')
        ->click('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-15"]');
});

it('navigates back to the previous day on btn-prev click', function (): void {
    visit('/test-timegrid-day')
        ->click('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-15"]')
        ->click('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="timegrid-day-2026-05-14"]');
});

it('navigates to today on btn-today click', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="timegrid-day-2026-05-14"]')
        ->click('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="timegrid-day-2026-01-15"]');
});

it('renders a calendar title', function (): void {
    visit('/test-timegrid-day')
        ->assertPresent('[data-testid="calendar-title"]');
});
