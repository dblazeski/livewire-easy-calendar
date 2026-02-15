# Draft: Livewire Calendar (FullCalendar Parity Spec)

I am recording our requirements + decisions here so we can generate a single ExecPlan later.

## Requirements (confirmed)
- We are creating our own Laravel package in this repo.
- UI stack: Laravel Livewire v4 + Alpine.js.
- Reference spec: FullCalendar beta docs (FullCalendar is a reference for features/options; we want parity).
- Dependency rule: no dependencies on FullCalendar packages/code (no `@fullcalendar/*` shipped/used). Clarification from user: "no dependencies" means "no dependencies on FullCalendar".
- Quality bar: TDD package. Every feature added must include:
  - Pest tests (PHP-level)
  - Pest Browser tests (Playwright-backed) covering the real UI behavior
- Formatting/linting: Laravel Pint.
- Debugging guideline: when debugging flow/usage, use Laravel `dump()`, `dd()`, and logs; remove debug statements after debugging.

## Requirements (confirmed decisions)
- Consumer API: PHP-first.
- Public API style: Laravel-native API (feature/option parity target, but we do not need to mirror FullCalendar option key names).
- Extension model: consumers extend our base Livewire component.
- Storage: storage-agnostic (no forced DB schema/model/migrations by default).
- Scheduler/premium parity: implement manual equivalents (resources/timeline/etc) without using FullCalendar premium packages.
- Allowed JS deps (non-FullCalendar): "Date + RRule only" (to improve correctness for timezone/DST and recurring events).
- Styling: ship a default theme, and make it overridable.

## Repo reality (observed)
- Package metadata: `composer.json` (PHP ^8.3, illuminate/support ^12, livewire/livewire ^4, spatie/laravel-package-tools).
- Test stack: `orchestra/testbench`, `pestphp/pest` ^4, `pestphp/pest-plugin-laravel` ^4, `pestphp/pest-plugin-browser` ^4.
- Current component skeleton:
  - `src/Livewire/LivewireCalendar.php` renders `resources/views/livewire-calendar.blade.php`.
  - `src/LivewireCalendarServiceProvider.php` registers views/assets and the component alias `livewire-calendar`.
  - `resources/js/livewire-calendar.ts` is a placeholder.
- JS tooling:
  - `vite.config.ts` builds an IIFE bundle to `resources/dist/livewire-calendar.js`.
  - `package.json` currently includes `@fullcalendar/*` beta dependencies (conflict with the "no FullCalendar dependency" rule; must be removed in execution).
- Browser-test scaffolding gaps (observed):
  - No test routes/pages for browser tests yet.
  - No `workbench/` app (optional, but common for package-level browser testing).

## Research findings (docs)
- Livewire 4 JS API exists for bridging JS <-> server:
  - `$wire.$call('method', ...params)`, `$wire.$set('prop', value)`
  - Events: `$wire.$dispatch(...)`, `$wire.$on(...)`, plus global `Livewire.dispatch(...)` / `Livewire.on(...)`
  - Source: https://livewire.laravel.com/docs/4.x/javascript
- Pest v4 browser testing (Playwright-backed) API pattern:
  - `$page = visit('/path')` then `click/fill/press/assertSee/...`
  - Source: https://pestphp.com/docs/browser-testing

## Research findings (FullCalendar inventory - reference spec)
- Official docs: https://fullcalendar.io/docs (current beta referenced by librarian: v7.0.0-beta.7)
- Core parity categories to mirror:
  - Views: month/dayGrid, timeGrid, list, multiMonth
  - Toolbar/navigation: headerToolbar/footerToolbar, prev/next/today/gotoDate
  - Event model + display: event object props, rendering hooks, ordering, colors
  - Event sources: arrays, JSON feeds, function sources; loading lifecycle
  - Interactions: click/hover, select/unselect, drag/drop/resize, constraints/allow/overlap
  - Date/time display: hiddenDays/weekends, slotDuration/labels, nowIndicator, weekNumbers, navLinks
  - Business hours, sizing, theming, localization, timezone
- Advanced (Scheduler-like) parity categories to mirror:
  - Resources (hierarchies, per-resource business hours/colors)
  - Timeline/resource timeline + vertical resource views
  - Resource grouping, resource area columns, filtering/sorting

## Architecture guidance (oracle)
- Keep the calendar surface JS-owned inside a `wire:ignore` island (avoid Livewire DOM diffing the dense calendar UI).
- Use Alpine as the client state/store; treat Livewire as a typed "data + command API" (load range, persist mutations).
- Call `$wire` only at interaction boundaries (nav end, drop end), not during pointermove.
- Implement view modules (month/time/list/multimonth/resources) behind a small interface, sharing a common event/resource store.
- Risks to plan for:
  - Timezones/DST correctness
  - Performance under load (many slots/events/resources)
  - Browser-test flakiness for drag/resize (need deterministic selectors and possibly a test-only helper)

## API surface idea (repo-informed; to be finalized in plan)
- Keep `Calendar\LivewireCalendar\Livewire\LivewireCalendar` as the base component consumers extend.
- Define DTO/value objects (storage-agnostic): `CalendarEvent`, `CalendarResource`, `DateRange`.
- Minimal abstract contract likely includes `fetchEvents(DateRange $range): array`.
- Optional hooks for resources and mutations: fetchResources, handleEventCreate/Update/Delete, authorize hooks, configureCalendar options.

## Open questions (need answers before ExecPlan is final)
- Browser-test scaffolding: prefer minimal Testbench routes, or add a `workbench/` app for more realistic browser tests?
- Asset strategy: should built assets in `resources/dist/` be committed to the repo and published, or always generated during install/CI?

## Note for later
- User asked to record the TDD/testing rule in `AGENTS.md`. Prometheus (planner) cannot edit files outside `.sisyphus/`, so this must be a TODO in the execution plan.
