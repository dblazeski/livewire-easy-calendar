# Livewire Calendar (Package) — Project Overview

## Purpose
- Laravel package providing a Livewire calendar component with multiple views and interactive behaviors.
- Implements its own rendering + interactions (does NOT depend on FullCalendar at runtime). FullCalendar is used only as a reference for view names and payload conventions.

## Tech Stack
- PHP: 8.3+
- Laravel: 12+
- Livewire: 4
- Package wiring: spatie/laravel-package-tools
- Recurrence: rlanvin/php-rrule
- Frontend build: TypeScript + Vite
- Browser tests: Pest + pest-plugin-browser (Playwright)

## Key Concepts
- Calendar is implemented by extending the base component class and returning events/resources for a requested DateRange.
- Rendering is Blade-first: default Blade views exist for each view, and consumers can override them via config or mount props with safe fallback.
- JS is interactions-only (drag/drop/resize/select/click) and talks to Livewire actions.

## Event Model (payload)
Required fields:
- id (string)
- title (string)
- start (ISO-8601 string)
- end (ISO-8601 string)

Optional fields:
- allDay (bool)
- rrule (string) + exdate (array of dates)
- resourceId (string) for resource views
- backgroundColor (hex) or color (hex alias)

Note: CalendarEvent value object supports extra metadata via `extra` array which is spread into the payload.

## Repo Structure (high level)
- src/: PHP package code (Livewire component, value objects, service provider)
- resources/views/: package Blade views
- resources/js/: TS + CSS sources (built to resources/dist)
- config/: publishable config
- tests/: Pest + Pest Browser tests
- workbench/: local docs site & examples via Orchestra Testbench Workbench
