# Blade-First Views With Safe User Overrides

This ExecPlan is a living document. The sections `Progress`, `Surprises & Discoveries`, `Decision Log`, and `Outcomes & Retrospective` must be kept up to date as work proceeds.

This repository contains `.agent/PLANS.md`. This ExecPlan must be maintained in accordance with `.agent/PLANS.md`.

## Purpose / Big Picture

Replace the current JavaScript-rendered calendar DOM with Blade-rendered default templates for each supported calendar view (`month`, `timeGridWeek`, `timeGridDay`, `listWeek`, `multiMonthYear`, `resourceTimelineDay`). Users should be able to override any of these templates by providing their own Blade view name; if a custom view does not exist, the calendar must fall back to the package default rather than throwing.

At the end, package consumers can style the calendar and event UI by editing/publishing Blade views (Tailwind-friendly), instead of relying on package CSS config tokens.

## Progress

- [ ] (2026-02-15) Decide the public override API for view templates (mount props + config keys).
- [ ] Implement view resolution with `view()->exists()` fallback.
- [ ] Implement Blade default templates for all view types (preserve existing `data-testid` contract).
- [ ] Move recurrence expansion to PHP so Blade views render occurrences deterministically.
- [ ] Reduce JavaScript to an interaction bridge (drag/drop/resize/select only); remove DOM creation.
- [ ] Update docs and tests to reflect Blade-first rendering and ensure all existing interaction/recurrence tests pass.

## Surprises & Discoveries

- Observation: The current recurrence behavior is implemented in `resources/js/livewire-calendar.ts` using `rrule` and treats date-only EXDATE values as “same time as DTSTART on that date”.
  Evidence: JS `buildRRuleSet()` sets date-only exdates to `dtstart` time before excluding.

- Observation: Existing browser tests assert specific `data-testid` values for grid cells, events, and interaction targets.
  Evidence: `tests/Feature/*Test.php` and `tests/Docs/DocsSiteBrowserTest.php` selectors.

## Decision Log

- Decision: Keep the existing `data-testid` selectors as a compatibility contract while porting rendering to Blade.
  Rationale: Minimizes test churn and preserves consumer automation hooks.
  Date/Author: 2026-02-15 / Sisyphus

- Decision: Use `view()->exists()` when resolving user-provided view names; otherwise return package defaults.
  Rationale: Explicit requirement: “if it doesn’t exist render the default one”.
  Date/Author: 2026-02-15 / Sisyphus

## Outcomes & Retrospective

- (Pending) Blade-first calendar rendering with safe view overrides implemented.

## Context and Orientation

The base Livewire component is `src/Livewire/LivewireCalendar.php`. It currently renders `resources/views/livewire-calendar.blade.php`, which mounts a `wire:ignore` root and includes built assets `resources/dist/livewire-calendar.js` and `resources/dist/livewire-calendar.css`.

Today, all calendar DOM (toolbar, grids, events) is created in JavaScript (`resources/js/livewire-calendar.ts`). Livewire is used mainly as a data provider (`fetchEvents`, `fetchResources`) and as a sink for interactions (`eventClick`, `dateSelect`, `eventDrop`, `eventResize`).

The new design must keep the Livewire extension surface area stable for consumers:

- consumers extend `Calendar\LivewireCalendar\Livewire\LivewireCalendar`
- they implement `protected function events(DateRange $range): array` and optionally `protected function resources(DateRange $range): array`
- they can override interaction hooks `onEventClick`, `onDateSelect`, `onEventDrop`, `onEventResize`

## Plan of Work

Implement Blade-first rendering in small, verifiable milestones that preserve the existing DOM contract.

1. Add a template override API to `src/Livewire/LivewireCalendar.php`.
   - Introduce a `public array $views = []` mount prop (and optional config key) mapping calendar view keys to Blade view names.
   - Implement `resolveViewFor(string $viewKey): string` that returns the custom view only if `view()->exists($custom)`.

2. Add package default Blade templates for each view key.
   - Create `resources/views/views/{viewKey}.blade.php` for all supported view keys.
   - Ensure they render the same `data-testid` attributes used by tests.

3. Move recurrence expansion from JS to PHP.
   - Add a PHP recurrence expander that mirrors the JS behavior and produces occurrence IDs like `{baseId}__{yyyyMMddTHHmmss}`.
   - Treat date-only EXDATE strings as “same time as DTSTART” in the calendar timezone.

4. Convert the existing JS bundle into an interaction-only bridge.
   - Keep drag/drop/resize/select client logic, but remove all DOM creation and “render events” code paths.
   - On interaction completion, call the existing Livewire methods and let Livewire re-render Blade.

5. Update tests + docs.
   - Add tests that verify custom view names fall back safely when missing.
   - Ensure existing browser tests still pass (or update them minimally if the DOM changes).

## Concrete Steps

All commands run from repository root `/Users/db/Code/livewire-easy-calendar`.

- Build assets: `npm run build`
- Run tests: `vendor/bin/pest`

## Validation and Acceptance

Acceptance is met when:

1. Each view key renders a Blade default template without relying on JS DOM creation.
2. Consumers can specify custom Blade view names; when a custom view does not exist, the package renders the default template.
3. Recurring events appear correctly in Blade-rendered output (boundary + EXDATE + DST tests pass).
4. Drag/drop/resize/select interactions still call the correct Livewire hooks and browser tests pass.

## Idempotence and Recovery

- This refactor touches rendering across all views. Keep changes small and keep tests green after each milestone.
- If an interaction breaks, temporarily disable the new Blade view for that view key and fall back to the previous implementation while fixing the bridge.

## Interfaces and Dependencies

- Livewire 4 component base: `src/Livewire/LivewireCalendar.php`
- Default templates: `resources/views/`
- Built assets: `resources/dist/`
- Browser tests: `pestphp/pest-plugin-browser`
