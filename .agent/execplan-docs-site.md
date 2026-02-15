# Build Workbench Documentation Site for Livewire Calendar

This ExecPlan is a living document. The sections `Progress`, `Surprises & Discoveries`, `Decision Log`, and `Outcomes & Retrospective` must be kept up to date as work proceeds.

This repository contains `.agent/PLANS.md`. This ExecPlan must be maintained in accordance with `.agent/PLANS.md`.

## Purpose / Big Picture

Create a developer-friendly documentation site, hosted via Orchestra Testbench Workbench inside this package repository, that documents all existing package features and includes runnable, SQLite-backed Laravel examples. The docs site must be verifiable via Pest Browser tests. It must be runnable locally without Docker on port 3010.

## Progress

- [x] (2026-02-15 13:05 Europe/Skopje) Inventory repository structure and confirm Workbench scaffold exists.
- [x] (2026-02-15 13:05 Europe/Skopje) Confirm official Workbench configuration keys (`workbench.discovers`, `workbench.sync`, `workbench.build`, etc.) from packages.tools docs.
- [ ] Add Workbench app code (routes, views, provider, demo components).
- [ ] Add Tailwind-based docs styling and ensure it is served by Workbench.
- [ ] Add committed SQLite fixture database and Eloquent models for demos.
- [ ] Add Pest Browser tests that verify docs pages and demos render.
- [ ] Update root `README.md` to include docs instructions.

## Surprises & Discoveries

- Observation: Testbench CLI can crash with duplicate provider/class declaration when Composer package discovery loads the package provider twice.
  Evidence: Error seen previously: `Cannot declare class Calendar\LivewireCalendar\LivewireCalendarServiceProvider, because the name is already in use`.
  Mitigation: Use `testbench.yaml` `dont-discover: ['*']` and explicit `providers:` list.

- Observation: Workbench `sync` only symlinks directories, not individual files.
  Evidence: `vendor/orchestra/testbench-core/src/Workbench/Actions/AddAssetSymlinkFolders.php` checks `isDirectory($from)` before linking.

## Decision Log

- Decision: Serve docs via Workbench routes/views under `workbench/` rather than adding a separate docs framework.
  Rationale: Workbench is already a dev dependency and provides `serve` + discovery.
  Date/Author: 2026-02-15 / Sisyphus

- Decision: Use Workbench `sync` for calendar JS/CSS during docs serving.
  Rationale: Avoid repeated `vendor:publish` copies; symlink keeps assets current.
  Date/Author: 2026-02-15 / Sisyphus

- Decision: Use a committed SQLite file as the canonical demo dataset.
  Rationale: User requirement: commit the SQLite file; tests can read stable fixture data.
  Date/Author: 2026-02-15 / Sisyphus

## Outcomes & Retrospective

- (Pending) Docs site + examples + tests implemented.

## Context and Orientation

This repository is a Laravel Livewire package. The actual calendar UI is rendered by `resources/js/livewire-calendar.ts` and styled by `resources/js/livewire-calendar.css`, which are built into `resources/dist/livewire-calendar.js` and `resources/dist/livewire-calendar.css`.

The package exposes a base Livewire component class at `src/Livewire/LivewireCalendar.php`. Consumers extend this class and implement:

- `protected function events(Calendar\LivewireCalendar\DateRange $range): array`
- `protected function resources(Calendar\LivewireCalendar\DateRange $range): array`
- interaction hooks `onEventClick`, `onDateSelect`, `onEventDrop`, `onEventResize`

The package view is `resources/views/livewire-calendar.blade.php` and expects published assets at `public/vendor/livewire-calendar/`.

Workbench scaffolding exists at `workbench/` (routes, views, app models/migrations/seeders directories). Workbench behavior is configured in `testbench.yaml`.

## Plan of Work

Implement a Workbench-only Laravel app (routes + Blade views) that acts as the documentation site.

1. Add Workbench bootstrap code:
   - Create a `Workbench` service provider (under `workbench/app/Providers/`) to:
     - Configure the database connection to use the committed SQLite file.
     - Register one or more demo Livewire components that extend the package `LivewireCalendar`.
   - Add the provider to `testbench.yaml` `providers:`.
   - Configure `workbench.discovers` to load `workbench/routes/web.php` and `workbench/resources/views`.
   - Configure `workbench.sync` so the Workbench skeleton serves:
     - `resources/dist` at `public/vendor/livewire-calendar` (calendar assets).
     - a docs assets directory (Tailwind CSS output) at a stable URL path.

2. Add the committed SQLite fixture and the Eloquent models used by demos.
   - Create `workbench/database/demo.sqlite` with stable event/resource records.
   - Add `workbench/app/Models/DemoEvent.php` and `workbench/app/Models/DemoResource.php` to query the fixture.

3. Create demo Livewire components backed by SQLite.
   - At minimum, one component that supports all views and returns events/resources from the fixture.
   - Implement interaction hooks and surface the last interaction payload in the demo UI.

4. Build the docs site.
   - Create `workbench/routes/web.php` with `/docs/*` pages.
   - Create Blade views under `workbench/resources/views/docs/` documenting:
     - installation + asset publishing
     - event/resource payload structures
     - supported views
     - recurrence (`rrule`, `exdate`) and timezone/DST behavior
     - interaction hooks (click/select/drop/resize)
     - runnable examples that embed the demo components

5. Add Tailwind styling for the docs site.
   - Add a Tailwind source file and a build output that is served via Workbench `sync`.
   - Prefer a self-contained, committed output CSS so `composer run docs` works without a separate Vite dev server.

6. Add Pest Browser tests for the docs site.
   - Add a Workbench-enabled test case using `Orchestra\Testbench\Concerns\WithWorkbench`.
   - Write browser tests that:
     - visit the docs home
     - visit key feature pages
     - assert embedded demo calendars render expected events/resources.

7. Update root `README.md`.
   - Add a section for running docs locally on port 3010.

## Concrete Steps

All commands run from repository root `/Users/db/Code/livewire-easy-calendar`.

- Run docs server:
  - `composer run docs`
  - Expected: server running at `http://127.0.0.1:3010` and `/docs` loads.

- Run tests:
  - `composer test`
  - Expected: all tests pass; new docs tests included.

## Validation and Acceptance

Acceptance is met when:

1. Running `composer run docs` starts a local server on port 3010 and `/docs` renders a styled docs homepage.
2. The docs site includes pages covering all package features and at least one SQLite-backed demo calendar.
3. The committed SQLite file exists in the repository and is used by the demo calendars.
4. Pest Browser tests verify that:
   - the docs pages render
   - the embedded demo calendars render expected event/resource data.

## Idempotence and Recovery

- Workbench `sync` creates symlinks under the Testbench skeleton. If assets appear stale, rerun `composer run build` or restart `composer run docs`.
- If the committed SQLite fixture is modified by mistake, restore it from git and ensure demos do not persist writes.

## Interfaces and Dependencies

- Laravel 12 + Livewire 4 are used via Testbench.
- Workbench configuration is provided by `testbench.yaml`.
- Demo calendars extend `Calendar\LivewireCalendar\Livewire\LivewireCalendar`.
- Browser tests use `pestphp/pest-plugin-browser` and will assert against DOM selectors already used elsewhere in this package (e.g. `data-testid`).
