# Livewire Calendar

A Livewire calendar component for Laravel with Alpine.js integration. Provides multiple calendar views (month, week, day, list, year, resource timeline) with event rendering, recurrence support, and interactive features.

## Installation

Install via Composer:

```bash
composer require dblazeski/livewire-calendar
```

Publish the package assets:

```bash
php artisan vendor:publish --tag=livewire-calendar-assets
```

This publishes CSS and JavaScript files to `public/vendor/livewire-calendar/`.

## Usage

### Basic Calendar Component

Create a Livewire component that extends `Calendar\LivewireCalendar\Livewire\LivewireCalendar`:

```php
<?php

namespace App\Livewire;

use Calendar\LivewireCalendar\DateRange;
use Calendar\LivewireCalendar\Livewire\LivewireCalendar;

class MyCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        // Return events for the given date range
        return [
            [
                'id' => '1',
                'title' => 'Team Meeting',
                'start' => '2026-05-14T09:00:00+00:00',
                'end' => '2026-05-14T10:00:00+00:00',
            ],
        ];
    }
}
```

Render the component in your Blade view:

```blade
<livewire:my-calendar />
```

### Event Data Structure

Events must include these required fields:

- `id` (string): Unique event identifier
- `title` (string): Event title
- `start` (string): ISO-8601 datetime (e.g., `2026-05-14T09:00:00+00:00`)
- `end` (string): ISO-8601 datetime

Optional fields:

- `allDay` (bool): Marks event as all-day
- `rrule` (string): Recurrence rule (e.g., `FREQ=DAILY;COUNT=5`)
- `exdate` (array): Exclusion dates for recurring events (e.g., `['2026-05-15']`)
- `resourceId` (string): Resource identifier for resource timeline views

### Using CalendarEvent Helper

Use the `CalendarEvent` value object for type safety:

```php
use Calendar\LivewireCalendar\CalendarEvent;
use Calendar\LivewireCalendar\DateRange;
use Illuminate\Support\Carbon;

protected function events(DateRange $range): array
{
    return [
        new CalendarEvent(
            id: '1',
            title: 'Team Meeting',
            start: Carbon::parse('2026-05-14T09:00:00+00:00'),
            end: Carbon::parse('2026-05-14T10:00:00+00:00'),
            extra: [
                'allDay' => false,
                'resourceId' => 'room-1',
            ]
        ),
    ];
}
```

The `CalendarEvent` constructor accepts Carbon instances for `start` and `end`. The `extra` array spreads into the top-level event payload, allowing arbitrary metadata.

### Supported Views

Pass the `view` prop to control the calendar view:

```blade
<livewire:my-calendar view="timeGridWeek" />
```

Available views:

- `month` (default): Monthly grid view
- `timeGridWeek`: Week view with time slots
- `timeGridDay`: Single day view with time slots
- `listWeek`: List view for a week
- `multiMonthYear`: 12-month year grid
- `resourceTimelineDay`: Single-day timeline with resource rows

### Component Props

Configure the calendar via mount parameters:

```php
public function mount(): void
{
    parent::mount(
        initialDate: '2026-05-14',  // YYYY-MM-DD
        firstDay: 0,                 // 0=Sunday, 1=Monday, etc.
        today: '2026-05-14',         // YYYY-MM-DD (for testing)
        view: 'month',               // View name
        timeZone: 'America/New_York' // IANA timezone
    );
}
```

All parameters are optional and have sensible defaults.

### Time Zone Handling

The `timeZone` prop controls date math in JavaScript. Event `start` and `end` values should include timezone offsets (ISO-8601 format). The calendar uses the specified timezone for:

- Navigation (prev/next/today buttons)
- Event placement in time slots
- Recurrence expansion

Default timezone: `config('app.timezone', 'UTC')`

### Interaction Hooks

Override these methods to handle user interactions:

```php
protected function onEventClick(string $eventId, array $eventData): void
{
    // Handle event click
}

protected function onDateSelect(string $start, string $end, bool $allDay): void
{
    // Handle date/time selection
}

protected function onEventDrop(string $eventId, string $newStart, string $newEnd): void
{
    // Handle event drag-and-drop
}

protected function onEventResize(string $eventId, string $newStart, string $newEnd): void
{
    // Handle event resize
}
```

All datetime parameters are ISO-8601 strings.

### Resources (for Resource Timeline Views)

Override the `resources()` method to provide resource data:

```php
use Calendar\LivewireCalendar\CalendarResource;

protected function resources(DateRange $range): array
{
    return [
        new CalendarResource(
            id: 'room-1',
            title: 'Conference Room A',
            extra: ['capacity' => 10]
        ),
    ];
}
```

Events must include a `resourceId` field to appear in the resource timeline:

```php
use Illuminate\Support\Carbon;

protected function events(DateRange $range): array
{
    return [
        new CalendarEvent(
            id: '1',
            title: 'Meeting',
            start: Carbon::parse('2026-05-14T09:00:00+00:00'),
            end: Carbon::parse('2026-05-14T10:00:00+00:00'),
            extra: ['resourceId' => 'room-1']
        ),
    ];
}
```

### Recurrence

Use the `rrule` field for recurring events:

```php
use Illuminate\Support\Carbon;

new CalendarEvent(
    id: 'daily-standup',
    title: 'Daily Standup',
    start: Carbon::parse('2026-05-14T09:00:00+00:00'),
    end: Carbon::parse('2026-05-14T09:30:00+00:00'),
    extra: [
        'rrule' => 'FREQ=DAILY;COUNT=5',
        'exdate' => ['2026-05-16'], // Exclude May 16
    ]
)
```

The `rrule` value follows the iCalendar RRULE format. The `exdate` array excludes specific dates from the recurrence.

## Architecture

This package does **not** depend on FullCalendar or any third-party calendar libraries. FullCalendar is used only as a specification reference for view names and event payload conventions. All rendering and interaction logic is implemented from scratch using Livewire, Alpine.js, and vanilla JavaScript.

## Testing

This package follows strict TDD practices using Pest and Pest Browser:

```bash
composer test
```

Browser tests require Playwright (installed automatically via `pestphp/pest-plugin-browser`).

## Documentation Site (Workbench)

This repository includes a local documentation site powered by Orchestra Testbench Workbench.

- Serve docs locally on port 3010:

```bash
composer docs
```

Open `http://127.0.0.1:3010/docs`.

Docs live under `workbench/resources/views/docs/` and include runnable examples backed by a committed SQLite fixture database at `workbench/database/demo.sqlite`.

If you change the docs Tailwind source at `workbench/resources/css/docs.css`, rebuild the compiled docs CSS:

```bash
npm run docs:css
```

## Requirements

- PHP 8.3+
- Laravel 12.0+
- Livewire 4.0+

## License

MIT
