<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

it('initializes the calendar root element when the JS bundle runs', function (): void {
    Route::get('/test-calendar', fn () => Blade::render(<<<'HTML'
        <html>
        <head>
            @livewireStyles
        </head>
        <body>
            @livewireScripts
            <livewire:livewire-calendar />
        </body>
        </html>
    HTML))->middleware('web');

    visit('/test-calendar')
        ->assertAttribute('[data-livewire-calendar-root]', 'data-livewire-calendar-initialized', 'true');
});
