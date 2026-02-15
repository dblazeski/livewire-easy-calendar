<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-month-grid', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar initial-date="2026-05-01" first-day="0" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders the month title for the initial date', function (): void {
    visit('/test-month-grid')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 2026');
});

it('renders exactly 42 day cells in a 6-week grid', function (): void {
    visit('/test-month-grid')
        ->assertCount('[data-date]', 42);
});

it('renders the first grid cell as the Sunday before month start', function (): void {
    visit('/test-month-grid')
        ->assertPresent('[data-testid="day-cell-2026-04-26"]')
        ->assertAttribute('[data-testid="day-cell-2026-04-26"]', 'data-current-month', 'false');
});

it('renders the last grid cell as the Saturday after month end', function (): void {
    visit('/test-month-grid')
        ->assertPresent('[data-testid="day-cell-2026-06-06"]')
        ->assertAttribute('[data-testid="day-cell-2026-06-06"]', 'data-current-month', 'false');
});

it('marks current-month days correctly', function (): void {
    visit('/test-month-grid')
        ->assertAttribute('[data-testid="day-cell-2026-05-01"]', 'data-current-month', 'true')
        ->assertAttribute('[data-testid="day-cell-2026-05-15"]', 'data-current-month', 'true')
        ->assertAttribute('[data-testid="day-cell-2026-05-31"]', 'data-current-month', 'true');
});

it('renders day-of-week headers', function (): void {
    visit('/test-month-grid')
        ->assertSeeIn('[data-testid="month-grid"]', 'Sun')
        ->assertSeeIn('[data-testid="month-grid"]', 'Mon')
        ->assertSeeIn('[data-testid="month-grid"]', 'Sat');
});
