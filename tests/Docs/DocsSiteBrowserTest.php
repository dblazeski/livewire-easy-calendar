<?php

it('renders the docs homepage with a working calendar demo', function (): void {
    visit('/docs')
        ->assertSee('Livewire')
        ->assertSee('Calendar')
        ->assertPresent('[aria-label="Documentation"]')
        ->assertPresent('[data-testid="calendar-toolbar"]')
        ->assertPresent('[data-testid="view-switcher"]')
        ->assertPresent('[data-testid="view-btn-month"]')
        ->assertPresent('[data-testid="timegrid"]')
        ->assertPresent('[data-testid="slot-cell-2026-05-14-09:00"]')
        ->click('[data-testid="view-btn-month"]')
        ->assertPresent('[data-testid="month-grid"]');
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

it('allows dragging a recurring occurrence in the recurring events example', function (): void {
    visit('/docs/examples/recurring')
        ->assertPresent('[data-testid="month-event-evt-standup__20260512T090000-2026-05-12"]')
        ->drag(
            '[data-testid="month-event-evt-standup__20260512T090000-2026-05-12"]',
            '[data-testid="day-cell-2026-05-16"]',
        )
        ->assertPresent('[data-testid="month-event-evt-standup__20260512T090000-2026-05-16"]')
        ->assertNotPresent('[data-testid="month-event-evt-standup__20260512T090000-2026-05-12"]');
});

it('renders the basic calendar example with drag-and-drop interactions', function (): void {
    visit('/docs/examples/basic')
        ->assertSee('Basic Calendar')
        ->assertPresent('[data-testid="month-grid"]')
        ->assertPresent('[data-testid="month-event-evt-movable-2026-05-14"]')
        ->drag(
            '[data-testid="month-event-evt-movable-2026-05-14"]',
            '[data-testid="day-cell-2026-05-15"]',
        )
        ->assertPresent('[data-testid="month-event-evt-movable-2026-05-15"]');
});
