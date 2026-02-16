<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('falls back to the default header element view when a configured component view does not exist', function (): void {
    config()->set('livewire-calendar.components.header-view-month', 'views-do-not-exist');

    Route::get('/test-header-component-fallback', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-header-component-fallback')
        ->assertPresent('[data-testid="view-btn-month"]');
});

it('renders a configured custom header element view when it exists', function (): void {
    app('view')->addLocation(dirname(__DIR__).'/views');
    config()->set('livewire-calendar.components.header-view-month', 'custom-month-button');

    Route::get('/test-header-component-custom', fn () => Blade::render(<<<'HTML'
        <html>
        <head>@livewireStyles</head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar view="month" initial-date="2026-05-01" first-day="0" today="2026-01-15" time-zone="UTC" />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-header-component-custom')
        ->assertPresent('[data-testid="custom-month-button"]')
        ->assertNotPresent('[data-testid="view-btn-month"]');
});

it('renders a mount-provided custom header element view when it exists', function (): void {
    app('view')->addLocation(dirname(__DIR__).'/views');

    Route::get('/test-header-component-mount', fn () => Blade::render(<<<'HTML'
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
                :components="['header-view-month' => 'custom-month-button']"
            />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-header-component-mount')
        ->assertPresent('[data-testid="custom-month-button"]')
        ->assertNotPresent('[data-testid="view-btn-month"]');
});
