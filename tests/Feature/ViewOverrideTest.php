<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('falls back to the default view when a configured custom view does not exist', function (): void {
    config()->set('livewire-calendar.views.month', 'views-do-not-exist');

    Route::get('/test-view-override-fallback', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-view-override-fallback')
        ->assertPresent('[data-testid="month-grid"]');
});

it('renders a configured custom view when it exists', function (): void {
    app('view')->addLocation(dirname(__DIR__).'/views');
    config()->set('livewire-calendar.views.month', 'custom-calendar-view');

    Route::get('/test-view-override-custom', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-view-override-custom')
        ->assertPresent('[data-testid="custom-calendar-view"]')
        ->assertNotPresent('[data-testid="month-grid"]');
});

it('renders a mount-provided custom view when it exists', function (): void {
    app('view')->addLocation(dirname(__DIR__).'/views');

    Route::get('/test-view-override-mount', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar
                view="month"
                initial-date="2026-05-01"
                first-day="0"
                today="2026-01-15"
                time-zone="UTC"
                :views="['month' => 'custom-calendar-view']"
            />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-view-override-mount')
        ->assertPresent('[data-testid="custom-calendar-view"]')
        ->assertNotPresent('[data-testid="month-grid"]');
});
