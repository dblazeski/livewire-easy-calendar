<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-month-nav', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar initial-date="2026-05-01" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('renders navigation buttons', function (): void {
    visit('/test-month-nav')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]');
});

it('shows the initial month title', function (): void {
    visit('/test-month-nav')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 2026');
});

it('navigates to the next month on btn-next click', function (): void {
    visit('/test-month-nav')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'June 2026');
});

it('navigates to the previous month on btn-prev click', function (): void {
    visit('/test-month-nav')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'June 2026')
        ->click('[data-testid="btn-prev"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 2026');
});

it('navigates to the deterministic today month on btn-today click', function (): void {
    visit('/test-month-nav')
        ->assertSeeIn('[data-testid="calendar-title"]', 'May 2026')
        ->click('[data-testid="btn-today"]')
        ->assertSeeIn('[data-testid="calendar-title"]', 'January 2026');
});

it('updates the grid cells after navigation', function (): void {
    visit('/test-month-nav')
        ->click('[data-testid="btn-next"]')
        ->assertPresent('[data-testid="day-cell-2026-06-01"]')
        ->assertAttribute('[data-testid="day-cell-2026-06-01"]', 'data-current-month', 'true');
});

it('preserves 42 day cells after navigation', function (): void {
    visit('/test-month-nav')
        ->click('[data-testid="btn-next"]')
        ->assertCount('[data-date]', 42);
});

it('emits the today attribute on the root element', function (): void {
    visit('/test-month-nav')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-today', '2026-01-15');
});
