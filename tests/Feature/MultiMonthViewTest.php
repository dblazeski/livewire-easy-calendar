<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test-multimonth', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="multiMonthYear" initial-date="2026-05-14" first-day="0" today="2026-01-15" />
        </body>
        </html>
    HTML))->middleware('web');
});

it('emits the multiMonthYear view attribute on the root element', function (): void {
    visit('/test-multimonth')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-view', 'multiMonthYear');
});

it('renders the multimonth-view container', function (): void {
    visit('/test-multimonth')
        ->assertPresent('[data-testid="multimonth-view"]');
});

it('renders a calendar title with the year', function (): void {
    visit('/test-multimonth')
        ->assertSeeIn('[data-testid="calendar-title"]', '2026');
});

it('renders the standard toolbar buttons', function (): void {
    visit('/test-multimonth')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="btn-prev"]')
        ->assertPresent('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="btn-next"]');
});

it('renders all twelve months', function (): void {
    visit('/test-multimonth')
        ->assertPresent('[data-testid="multimonth-month-2026-01"]')
        ->assertPresent('[data-testid="multimonth-month-2026-02"]')
        ->assertPresent('[data-testid="multimonth-month-2026-03"]')
        ->assertPresent('[data-testid="multimonth-month-2026-04"]')
        ->assertPresent('[data-testid="multimonth-month-2026-05"]')
        ->assertPresent('[data-testid="multimonth-month-2026-06"]')
        ->assertPresent('[data-testid="multimonth-month-2026-07"]')
        ->assertPresent('[data-testid="multimonth-month-2026-08"]')
        ->assertPresent('[data-testid="multimonth-month-2026-09"]')
        ->assertPresent('[data-testid="multimonth-month-2026-10"]')
        ->assertPresent('[data-testid="multimonth-month-2026-11"]')
        ->assertPresent('[data-testid="multimonth-month-2026-12"]');
});

it('renders month titles inside each month container', function (): void {
    visit('/test-multimonth')
        ->assertSeeIn('[data-testid="multimonth-month-2026-01"]', 'January')
        ->assertSeeIn('[data-testid="multimonth-month-2026-06"]', 'June')
        ->assertSeeIn('[data-testid="multimonth-month-2026-12"]', 'December');
});

it('renders day cells within each month grid', function (): void {
    visit('/test-multimonth')
        ->assertPresent('[data-testid="multimonth-month-2026-01"] [data-date="2026-01-01"]')
        ->assertPresent('[data-testid="multimonth-month-2026-01"] [data-date="2026-01-31"]')
        ->assertPresent('[data-testid="multimonth-month-2026-12"] [data-date="2026-12-01"]')
        ->assertPresent('[data-testid="multimonth-month-2026-12"] [data-date="2026-12-31"]');
});

it('marks current-month cells correctly within a month', function (): void {
    visit('/test-multimonth')
        ->assertPresent('[data-testid="multimonth-month-2026-05"] [data-date="2026-05-01"][data-current-month="true"]')
        ->assertPresent('[data-testid="multimonth-month-2026-05"] [data-date="2026-05-31"][data-current-month="true"]');
});

it('navigates to the next year on btn-next click', function (): void {
    visit('/test-multimonth')
        ->assertSeeIn('[data-testid="calendar-title"]', '2026')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', '2027')
        ->assertPresent('[data-testid="multimonth-month-2027-01"]')
        ->assertPresent('[data-testid="multimonth-month-2027-12"]');
});

it('navigates to the previous year on btn-prev click', function (): void {
    visit('/test-multimonth')
        ->assertSeeIn('[data-testid="calendar-title"]', '2026')
        ->click('[data-testid="btn-prev"]')
        ->assertSeeIn('[data-testid="calendar-title"]', '2025')
        ->assertPresent('[data-testid="multimonth-month-2025-01"]');
});

it('navigates to the year containing today on btn-today click', function (): void {
    visit('/test-multimonth')
        ->click('[data-testid="btn-next"]')
        ->assertSeeIn('[data-testid="calendar-title"]', '2027')
        ->click('[data-testid="btn-today"]')
        ->assertSeeIn('[data-testid="calendar-title"]', '2026')
        ->assertPresent('[data-testid="multimonth-month-2026-01"]');
});
