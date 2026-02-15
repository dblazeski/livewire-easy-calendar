<?php

it('renders the docs homepage with a working calendar demo', function (): void {
    visit('/docs')
        ->assertSee('Livewire')
        ->assertSee('Calendar')
        ->assertPresent('[aria-label="Documentation"]')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="timegrid"]')
        ->assertPresent('[data-testid="slot-cell-2026-05-14-09:00"]');
});

it('renders the installation page', function (): void {
    visit('/docs/installation')
        ->assertSee('Installation')
        ->assertSee('vendor:publish')
        ->assertSee('livewire-calendar-assets');
});

it('renders the resource timeline example with resources', function (): void {
    visit('/docs/examples/resources')
        ->assertSee('Resource Scheduling')
        ->assertPresent('[data-testid="resource-timeline"]')
        ->assertPresent('[data-testid="resource-row-room-a"]')
        ->assertPresent('[data-testid="resource-row-room-b"]')
        ->assertPresent('[data-testid="resource-row-projector-1"]');
});

it('renders the recurring events example with expanded occurrences', function (): void {
    visit('/docs/examples/recurring')
        ->assertPresent('[data-testid="day-cell-2026-05-11"]')
        ->assertSeeIn('[data-testid="day-cell-2026-05-11"]', 'Daily Standup')
        ->assertPresent('[data-testid="day-cell-2026-05-12"]')
        ->assertSeeIn('[data-testid="day-cell-2026-05-12"]', 'Daily Standup')
        ->assertPresent('[data-testid="day-cell-2026-05-14"]')
        ->assertSeeIn('[data-testid="day-cell-2026-05-14"]', 'Daily Standup');
});
