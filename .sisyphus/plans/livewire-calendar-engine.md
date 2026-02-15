# Build Livewire Calendar Engine (No FullCalendar Dependency)

This ExecPlan is a living document. The sections `Progress`, `Surprises & Discoveries`, `Decision Log`, and `Outcomes & Retrospective` must be kept up to date as work proceeds.

This plan must be maintained in accordance with `.agent/PLANS.md`.

## Purpose / Big Picture

Deliver a Composer-installable Laravel package that provides a calendar UI component built with Livewire v4 + Alpine.js.

The calendar must be implemented without depending on FullCalendar packages/code. FullCalendar beta documentation is used only as a reference/spec to ensure feature/behavior parity over time.

Consumers should be able to build their own calendar by extending a base Livewire component class (storage-agnostic) and implementing methods for loading events (and later resources). The package ships a default, overridable theme.

This is a strict TDD package: every feature added must include both Pest tests and Pest Browser tests.

## Progress

- [x] (2026-02-14) Milestone 0: Remove FullCalendar npm deps, unblock `npm install`, install Playwright, and produce publishable built assets in `resources/dist/`. **Completed 2026-02-14.**
- [x] (2026-02-14) Milestone 1: Establish the base PHP contracts (consumer-extends-base) and the minimal JS/Alpine mount inside a `wire:ignore` island, with passing Pest + Pest Browser tests. **Completed 2026-02-14.**
- [x] (2026-02-14) Milestone 2: Implement Month view (grid) + navigation + basic event rendering for a visible date range, fully covered by tests. **Completed 2026-02-14.**
- [x] (2026-02-14) Milestone 3: Implement Week/Day time grid views (time slots, all-day row, timed event layout), fully covered by tests. **Completed 2026-02-14.**
- [x] (2026-02-14) Milestone 4: Implement List + Multi-month views, fully covered by tests. **Completed 2026-02-14.**
- [x] (2026-02-14) Milestone 5: Add interactions (click/select/drag/drop/resize) with Livewire mutation hooks, fully covered by tests. **Completed 2026-02-14.**
- [x] (2026-02-15) Milestone 6: Add recurrence (RRULE/EXDATE) and timezone/DST correctness using the allowed JS deps, fully covered by tests. **Completed 2026-02-15.**
- [x] (2026-02-15) Milestone 7: Add Scheduler-like resources + timeline views (manual equivalents), fully covered by tests. **Completed 2026-02-15.**
- [x] (2026-02-15) Milestone 8: Package polish: install/docs/CI, asset publishing UX, and a clean public API. **Completed 2026-02-15.**

## Surprises & Discoveries

- Observation: The repo previously contained FullCalendar npm devDependencies even though the requirement is "no FullCalendar dependency".
  Evidence: `package.json` listed `@fullcalendar/*` entries.
  **Resolved in Milestone 0**: All FullCalendar dependencies removed.
- Observation: Because of those FullCalendar beta deps, `npm install` previously failed and blocked Playwright installation (which blocked Pest Browser tests).
  Evidence: `npm install` error for `@fullcalendar/locales-all@beta` (404) and an npm auth warning about an expired/revoked token.
  **Resolved in Milestone 0**: `npm install` now succeeds.
- Observation: `composer.json` description previously claimed "FullCalendar-powered".
  Evidence: `composer.json:3`.
  **Resolved in Milestone 0**: Description updated to remove FullCalendar mention.
- Observation: Asset publishing is already wired via Spatie package-tools `->hasAssets()`, which expects built assets in `resources/dist/` and publishes them to `public/vendor/livewire-calendar/` via tag `livewire-calendar-assets`.
  Evidence: `src/LivewireCalendarServiceProvider.php:12-18` and spatie docs in `vendor/spatie/laravel-package-tools/README.md` (Assets section).
- Observation: Pest Browser plugin auto-wraps Livewire components with a minimal HTML shell including `@livewireStyles` and `@livewireScripts`, but it will not automatically include our package JS/CSS unless our component view includes it (or tests define a page that includes it).
  Evidence: `vendor/pestphp/pest-plugin-browser/src/Api/Livewire.php`.
- **Milestone 0 Discoveries**:
  - `package.json` no longer contains any `@fullcalendar/*` dependencies (verified via `grep @fullcalendar package.json` returning no matches).
  - `npm install` now succeeds; installed dependencies include `luxon@3.7.2`, `rrule@2.8.1`, `playwright@1.58.2`, `typescript@5.9.3`, and `vite@7.3.1`.
  - Playwright version installed: `1.58.2` (verified via `./node_modules/.bin/playwright run-server --version`).
  - `npm run build` successfully produced `resources/dist/livewire-calendar.js` (30 bytes; minimal placeholder bundle).
  - `composer.json` description no longer mentions FullCalendar.
  - Evidence: `.sisyphus/evidence/livewire-calendar-engine-m0.txt`.
- **Milestone 1 Discoveries**:
  - Pest Browser test detection: The `Pest\Browser\Api\Livewire::test()` helper is designed for Feature tests but does not automatically serve package assets from `resources/dist/` in Testbench. To prove JS initialization in a browser context, we used `visit()` with a dedicated test route that includes the component and explicitly loads published assets via `asset('vendor/livewire-calendar/...')`.
  - Asset-serving approach in Testbench: Package assets must be copied to the Testbench public path before browser tests run. The `TestCase::setUp()` method now copies `resources/dist/*` to `public/vendor/livewire-calendar/` so `asset()` calls resolve correctly during browser tests without requiring `php artisan vendor:publish` in the test environment.
  - Evidence: `.sisyphus/evidence/livewire-calendar-engine-m1.txt`.
- **Milestone 2 Discoveries**:
  - Month grid defaults: The calendar always renders a fixed 6-week grid (42 cells) to prevent layout shifts during navigation. This matches FullCalendar's `fixedWeekCount: true` and `showNonCurrentDates: true` defaults. The grid starts on the Sunday before the first day of the month and ends on the Saturday after the last day of the month.
  - Deterministic today: For testing stability, the calendar accepts a `today` attribute (ISO date string) that overrides the client-side "today" date. When not provided, the JS uses the current date. This allows browser tests to assert navigation behavior without flakiness from date changes.
  - Visible range computation: The Alpine component computes the visible range (start/end ISO strings) from the current month and emits it via `data-visible-start` and `data-visible-end` attributes on the root element. The JS calls `$wire.$call('fetchEvents', start, end)` whenever the visible range changes (initial mount or navigation).
  - Event placement: Events are rendered by the JS after receiving the `fetchEvents` response. The JS finds the day cell matching the event's start date (via `data-date` attribute) and appends a `<div class="calendar-event">` with the event title. Multiple events in the same day are stacked vertically. The Blade template provides the grid structure; the JS populates events dynamically.
   - Evidence: `.sisyphus/evidence/livewire-calendar-engine-m2.txt`.
- **Milestone 3 Discoveries**:
  - All-day detection: Events with `allDay === true` in the serialized payload (from PHP `CalendarEvent::$extra` spread) are separated from timed events and rendered into the all-day row cells instead of the timed events layer.
  - Overlap algorithm: Greedy column packing with deterministic sort (startMin asc → duration desc → id asc). Events are grouped into overlap clusters, then columns assigned left-to-right. Each event gets `data-col` and `data-col-count` attributes for test assertions.
  - Idempotent rendering: `renderTimeGridEvents` clears existing timed and all-day event DOM nodes before re-rendering, preventing duplicates when `loadTimeGridEvents` is called twice for the same view.
  - `renderTimeGrid` return type changed from `HTMLElement` to `{ eventsLayer, alldayRow }` to thread the allday row through the call chain for all-day event rendering.

- **Milestone 5 Discoveries**:
  - Slot cells covered by events layer: The `.lec-timegrid-events-layer` (position: absolute, pointer-events: none) visually covers `.lec-slot-cell` elements. Despite `pointer-events: none`, Playwright's actionability check considers slot cells obscured and times out on `click()` or `drag()` targeting them. Workaround: use `Webpage::script()` to dispatch `MouseEvent('mousedown')` + `MouseEvent('mouseup')` directly on the slot cell DOM element, bypassing Playwright's hit-test.
  - Deterministic click suppression: A time-based `lastInteractionTime` guard (100ms window) to prevent spurious click events after drag/resize was too aggressive — it suppressed legitimate clicks on different elements. Replaced with a boolean `suppressNextClick` flag set only after drag/resize with movement, cleared on the next click event. This is deterministic and doesn't depend on timing.
  - Pest Browser `drag()` works for timed events: `drag('[data-testid="timed-event-..."]', '[data-testid="slot-cell-..."]')` succeeds because timed events have `pointer-events: auto` and are above the events layer. The source element passes Playwright's actionability check.
  - PHP hooks pattern: Public Livewire actions (`eventClick`, `dateSelect`, `eventDrop`, `eventResize`) delegate to protected `on*` hooks with empty defaults. Consumers override the `on*` hooks. `eventDrop` and `eventResize` accept range params and return `fetchEvents()` result for immediate re-render without extra round-trip.

- **Milestone 7 Discoveries**:
  - `resourceTimelineDay` loads both resources and events for the visible day range and renders events into `.lec-resource-timeline-lane[data-resource-id]` using `event.resourceId` (top-level extra field). Recurring events expand via `expandRecurringEvents()` and are rendered with stable `data-testid="resource-event-<eventId>-<resourceId>"` selectors.

## Decision Log

- Decision: FullCalendar is a reference/spec only; the shipped package must not depend on any FullCalendar packages or code.
  Rationale: User requirement ("no fullcalendar dependency").
  Date/Author: 2026-02-14 / Prometheus
- Decision: Public API is Laravel-native (we do not need to mirror FullCalendar option key names).
  Rationale: User choice; reduces coupling to FullCalendar naming.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Consumers extend a base Livewire component class to integrate domain logic (storage-agnostic).
  Rationale: User choice; avoids forcing migrations/models.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Allowed JS deps (non-FullCalendar) are limited to a date/time library + an RRULE library (Luxon + rrule.js are the planned candidates).
  Rationale: User choice; keeps dependency surface small while addressing DST/timezone and recurrence correctly.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Testing policy is strict TDD with both Pest and Pest Browser tests for every feature.
  Rationale: User requirement; recorded in `AGENTS.md`.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Ship a default theme and make it overridable.
  Rationale: User choice; good out-of-box UX without blocking customization.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Calendar DOM is a `wire:ignore` island, with Alpine owning client state; Livewire provides data + mutation endpoints.
  Rationale: Avoid Livewire morph performance issues in dense calendar DOM; aligns with Livewire recommended third-party JS patterns.
  Date/Author: 2026-02-14 / Prometheus
- Decision: Browser tests use `visit()` with a dedicated test route rather than `Pest\Browser\Api\Livewire::test()` for component testing.
  Rationale: The `Livewire::test()` helper in Pest Browser wraps components in a minimal HTML shell but does not automatically serve package assets from `resources/dist/` in Testbench. Using `visit()` with a route that includes `@livewireStyles`, `@livewireScripts`, and explicit `<script src="{{ asset('vendor/livewire-calendar/livewire-calendar.js') }}">` allows full control over asset loading and proves the JS bundle initializes correctly in a realistic browser context. The `TestCase::setUp()` method copies built assets to the Testbench public path so `asset()` calls resolve during tests.
  Date/Author: 2026-02-14 / Sisyphus-Junior
- Decision: Month grid always renders 42 cells (6 weeks) to prevent layout shifts during navigation.
  Rationale: Matches FullCalendar's default behavior (`fixedWeekCount: true`, `showNonCurrentDates: true`). Provides a stable, predictable grid size that simplifies CSS layout and avoids jarring height changes when navigating between months with different week counts (e.g., February with 4 weeks vs. months with 6 weeks).
  Date/Author: 2026-02-14 / Sisyphus-Junior
- Decision: Calendar accepts a `today` attribute (ISO date string) to override the client-side "today" date for testing.
  Rationale: Enables deterministic browser tests for navigation (especially "today" button behavior) without flakiness from date changes. When `today` is not provided, the JS uses the current date. This pattern is common in calendar libraries for testing and demo purposes.
  Date/Author: 2026-02-14 / Sisyphus-Junior
- Decision: Event rendering is JS-driven: the Blade template provides the grid structure, and the JS populates events dynamically after fetching.
  Rationale: Keeps the Livewire component stateless on the server (no need to track current month or events in PHP properties). The JS owns the visible range and triggers `fetchEvents` via `$wire.$call()` whenever the range changes. This aligns with the `wire:ignore` island pattern and avoids Livewire morph performance issues in dense event DOM.
  Date/Author: 2026-02-14 / Sisyphus-Junior

## Outcomes & Retrospective

### Milestone 0 (Completed 2026-02-14)

**What was achieved**:
- Removed all FullCalendar npm dependencies from `package.json`.
- Unblocked `npm install` (now succeeds without errors).
- Installed Playwright 1.58.2 for Pest Browser tests.
- Added allowed JS dependencies: Luxon 3.7.2 (date/time/timezone) and rrule 2.8.1 (recurrence).
- Produced publishable built asset `resources/dist/livewire-calendar.js` via `npm run build`.
- Updated `composer.json` description to remove misleading "FullCalendar-powered" claim.

**What remains**:
- Milestone 1: Establish the PHP extension contract (consumer-extends-base) and implement the minimal JS/Alpine mount with a first browser test proving the bundle loads in Testbench.
- Milestones 2-8: Implement calendar views, interactions, recurrence/timezone correctness, resources/timeline, and packaging polish.

### Milestone 1 (Completed 2026-02-14)

**What was achieved**:
- Established the base PHP extension contract: `LivewireCalendar` component with `fetchEvents(DateRange $range)` method and protected `events()` hook for consumers to override.
- Created storage-agnostic value objects: `DateRange` (start/end dates) and `CalendarEvent` (id, title, start, end, allDay, extendedProps).
- Implemented minimal JS initialization: `resources/js/livewire-calendar.ts` sets `data-calendar-initialized="true"` on the root element when the bundle runs.
- Added CSS entrypoint: `resources/js/livewire-calendar.css` with minimal placeholder styles.
- Built publishable assets: `resources/dist/livewire-calendar.js` (272 bytes) and `resources/dist/livewire-calendar.css` (53 bytes).
- Created Blade view: `resources/views/livewire-calendar.blade.php` with `wire:ignore` root, explicit asset loading via `asset()`, and `@livewire('livewire-calendar')` rendering.
- Implemented asset-serving strategy for Testbench: `tests/TestCase.php` copies `resources/dist/*` to `public/vendor/livewire-calendar/` in `setUp()` so browser tests can load assets without manual publishing.
- Added Pest Feature tests: `tests/Feature/FetchEventsTest.php` (5 tests covering default behavior, consumer hook, normalization, date range passing, and extra fields preservation).
- Added Pest Browser test: `tests/Feature/JsInitializationTest.php` (1 test proving JS bundle loads and initializes the root element via `visit()` route).
- All tests pass: 9 passed (13 assertions) in 0.97s.
- Evidence: `.sisyphus/evidence/livewire-calendar-engine-m1.txt`.

**What remains**:
- Milestone 2: Implement Month view (grid rendering, navigation, basic event rendering for visible date range).
- Milestones 3-8: Time grid views, list/multi-month views, interactions, recurrence/timezone, resources/timeline, and packaging polish.

### Milestone 2 (Completed 2026-02-14)

**What was achieved**:
- Implemented month grid rendering: Always renders a fixed 6-week grid (42 cells) starting on the Sunday before the first day of the month and ending on the Saturday after the last day of the month. Cells are marked with `data-current-month="true"` for days in the current month.
- Added navigation controls: Prev/Next/Today buttons update the current month and trigger event re-fetching. The "Today" button navigates to the month containing the deterministic `today` date (or current date if `today` attribute is not provided).
- Implemented visible range computation: The Alpine component computes the visible range (start/end ISO strings) from the current month and emits it via `data-visible-start` and `data-visible-end` attributes on the root element.
- Implemented event fetching and rendering: The JS calls `$wire.$call('fetchEvents', start, end)` on mount and after navigation. Events are rendered by finding the day cell matching the event's start date (via `data-date` attribute) and appending a `<div class="calendar-event">` with the event title. Multiple events in the same day are stacked vertically.
- Added deterministic `today` attribute: The calendar accepts a `today` attribute (ISO date string) to override the client-side "today" date for testing. This enables stable browser tests for navigation behavior.
- Built publishable assets: `resources/dist/livewire-calendar.js` (74.91 kB, gzip: 23.39 kB) and `resources/dist/livewire-calendar.css` (1.15 kB, gzip: 0.53 kB).
- Added Pest Browser tests:
  - `tests/Feature/MonthGridTest.php` (6 tests covering grid structure, cell count, boundary dates, current-month marking, and day-of-week headers).
  - `tests/Feature/MonthNavigationTest.php` (8 tests covering navigation buttons, month title updates, prev/next/today behavior, grid updates after navigation, and the `today` attribute).
  - `tests/Feature/EventRenderingTest.php` (4 tests covering single event rendering, multiple events, day number preservation, and event re-fetching after navigation).
- All tests pass: 27 passed (50 assertions) in 3.29s.
- Evidence: `.sisyphus/evidence/livewire-calendar-engine-m2.txt`.
- Files created/modified:
  - `resources/js/livewire-calendar.ts` (Alpine component with month grid logic, navigation, and event rendering)
  - `resources/js/livewire-calendar.css` (grid layout styles)
  - `resources/views/livewire-calendar.blade.php` (grid structure with navigation controls)
  - `src/Livewire/LivewireCalendar.php` (no changes; `fetchEvents` method already existed from Milestone 1)
  - `tests/Feature/MonthGridTest.php` (new)
  - `tests/Feature/MonthNavigationTest.php` (new)
  - `tests/Feature/EventRenderingTest.php` (new)

**What the user can do now**:
- Render a month grid calendar with prev/next/today navigation.
- See events rendered in the correct day cells (event titles only; no time display yet).
- Navigate between months and see events re-fetch automatically for the new visible range.
- Use the `today` attribute for deterministic testing or demo purposes.

**What is next (Milestone 3)**:
- Implement Week and Day time-grid views with time slots (e.g., 00:00-23:00 in 30-minute increments).
- Add an all-day row above the time grid for all-day events.
- Implement timed event rendering at correct vertical positions based on start/end times.
- Implement a simple deterministic layout algorithm for overlapping timed events (e.g., side-by-side columns).
- Add Pest Browser tests covering time slot rendering, timed event placement, and overlap layout.

### Milestone 3 (Completed 2026-02-14)

**What was achieved**:
- Implemented Week and Day time-grid views with time slots (48 × 30-min slots per day), all-day row, timed event rendering with midnight-spanning split, and navigation.
- Added all-day cell data attributes (`data-testid="allday-cell-YYYY-MM-DD"`, `data-date="YYYY-MM-DD"`) for test targeting.
- Implemented all-day event rendering: events with `allDay === true` (from PHP `CalendarEvent::$extra` spread) are rendered into the allday row cells. Multi-day all-day events span multiple cells.
- Implemented timed-event overlap layout: greedy column-packing algorithm with deterministic sort (startMin asc → duration desc → id asc). Overlapping events are displayed side-by-side with `left`/`width` inline styles. Each event gets `data-col` and `data-col-count` attributes.
- Added idempotent rendering: `renderTimeGridEvents` clears previous events before re-rendering, preventing duplicate DOM nodes.
- Built publishable assets: `resources/dist/livewire-calendar.js` (81.54 kB, gzip: 24.94 kB) and `resources/dist/livewire-calendar.css` (2.75 kB, gzip: 0.83 kB).
- Added Pest Browser tests:
  - `tests/Feature/TimeGridAllDayEventsTest.php` (8 tests covering allday cell attributes, single-day/multi-day allday events, separation from timed events, day view allday rendering).
  - `tests/Feature/TimeGridOverlapTest.php` (5 tests covering two-event overlap, three-event overlap, non-overlapping isolation, attribute preservation, column count correctness).
- All tests pass: 69 passed (178 assertions). Zero regressions on existing 56 tests.
- Files modified:
  - `resources/js/livewire-calendar.ts` (overlap algorithm, all-day rendering, idempotent rendering, function signature changes)
  - `resources/js/livewire-calendar.css` (allday-event styles, timed-event box-sizing, allday-cell padding)
  - `tests/Feature/TimeGridAllDayEventsTest.php` (new)
  - `tests/Feature/TimeGridOverlapTest.php` (new)

**What the user can do now**:
- Render week and day time-grid views with 30-min time slots, all-day row, and timed event placement.
- See all-day events in the all-day row (single-day and multi-day).
- See overlapping timed events displayed side-by-side in deterministic column layout.
- Navigate between weeks/days and see events re-fetch automatically.

**What is next (Milestone 4)**:
- Implement List and Multi-month views with navigation and event rendering.
- Add Pest Browser tests covering list ordering and multi-month rendering.

### Milestone 4 (Completed 2026-02-14)

**What was achieved**:
- Implemented `listWeek` view: renders events grouped by day for a 7-day week range, with deterministic sorting (start asc → end asc → id asc). Events are fetched via `$wire.$call('fetchEvents', start, end)` with render-token stale protection.
- Implemented `multiMonthYear` view: renders 12 mini month grids in a 3-column CSS grid layout, each with 42 day cells (6-week grid), day-of-week headers, and month titles. Navigation moves by ±1 year.
- Navigation: `listWeek` uses ±7 days (same as `timeGridWeek`); `multiMonthYear` uses ±1 year. Today button jumps to week/year containing deterministic today date.
- Built publishable assets: `resources/dist/livewire-calendar.js` (85.81 kB, gzip: 25.73 kB) and `resources/dist/livewire-calendar.css` (4.01 kB, gzip: 1.03 kB).
- Added Pest Browser tests:
  - `tests/Feature/ListViewTest.php` (13 tests covering container rendering, view attribute, toolbar, event grouping by day, event selectors, event titles, time display, all-day labels, deterministic sorting, and prev/next/today navigation).
  - `tests/Feature/MultiMonthViewTest.php` (11 tests covering container rendering, view attribute, year title, toolbar, all 12 months present, month titles, day cells within months, current-month marking, and prev/next/today navigation).
- All tests pass: 93 passed (246 assertions). Zero regressions on existing 69 tests.
- Files modified:
  - `resources/js/livewire-calendar.ts` (added `renderListWeek`, `loadListEvents`, `renderListEvents`, `renderMultiMonthYear` functions; updated `renderCalendar` routing and navigation; updated `initializeRoots` anchor computation; updated `livewire:initialized` handler for listWeek)
  - `resources/js/livewire-calendar.css` (added list view and multi-month view styles)
  - `tests/Feature/ListViewTest.php` (new)
  - `tests/Feature/MultiMonthViewTest.php` (new)

**What the user can do now**:
- Render a list view (`listWeek`) showing upcoming events in chronological order, grouped by day.
- Render a multi-month year view (`multiMonthYear`) showing all 12 months of a year with navigation.
- Navigate between weeks (list) and years (multi-month) with prev/next/today buttons.

**What is next (Milestone 5)**:
- Add interactions (click/select/drag/drop/resize) with Livewire mutation hooks.
- Add Pest Browser tests covering each interaction.

### Milestone 5 (Completed 2026-02-14)

**What was achieved**:
- Implemented 4 PHP interaction hooks on `LivewireCalendar`: public Livewire actions `eventClick`, `dateSelect`, `eventDrop`, `eventResize` that delegate to protected `onEventClick`, `onDateSelect`, `onEventDrop`, `onEventResize` hooks (empty defaults, consumer-overridable).
- `eventDrop` and `eventResize` accept visible range params (`rangeStart`, `rangeEnd`) and return `fetchEvents()` result, enabling immediate client-side re-render without an extra Livewire round-trip.
- Implemented JS interaction system with global event delegation (mousedown/mousemove/mouseup/click on `document`):
  - **Click**: Toggles `data-selected="true"` on timed events, calls `eventClick` Livewire action.
  - **Select**: Mousedown on slot cell creates a selection overlay (`[data-testid="timegrid-selection"]`) with `data-start`/`data-end` attributes. Drag extends the selection across slot cells. Mouseup calls `dateSelect` Livewire action.
  - **Drag/Drop**: Mousedown on timed event starts drag. Mousemove adds `lec-event--dragging` class. Mouseup on a slot cell calls `eventDrop` with new start/end, then re-renders events from the response.
  - **Resize**: Mousedown on `.lec-resize-handle` starts resize. Mousemove updates event height visually. Mouseup calls `eventResize` with new end, then re-renders events from the response.
- Added stable selectors: `data-testid="slot-cell-YYYY-MM-DD-HH:MM"`, `data-testid="timed-event-resize-handle-<id>-<YYYY-MM-DD>"`, `data-testid="timegrid-selection"`. Timed events gained `data-event-start`, `data-event-end` attributes.
- Added CSS: `.lec-resize-handle`, `.lec-event--selected`, `.lec-event--dragging`, `.lec-event--resizing`, `.lec-timegrid-selection`, `.lec-timed-event-title`.
- Built publishable assets: `resources/dist/livewire-calendar.js` (92.40 kB, gzip: 27.18 kB) and `resources/dist/livewire-calendar.css` (4.56 kB, gzip: 1.19 kB).
- Added Pest tests:
  - `tests/Feature/InteractionHooksTest.php` (4 tests covering all 4 PHP hooks via `Livewire::test()`).
  - `tests/Feature/InteractionBrowserTest.php` (10 browser tests covering selector rendering, click toggle, selection creation, selection clearing, drag/drop, and resize).
- All tests pass: 107 passed (284 assertions). Zero regressions on existing 93 tests.
- Files modified:
  - `src/Livewire/LivewireCalendar.php` (added 4 public actions + 4 protected hooks)
  - `resources/js/livewire-calendar.ts` (added interaction handlers, global event delegation, `RootContext` WeakMap, `suppressNextClick` flag)
  - `resources/js/livewire-calendar.css` (added interaction styles)
  - `tests/Feature/InteractionHooksTest.php` (new)
  - `tests/Feature/InteractionBrowserTest.php` (new)

**What the user can do now**:
- Click timed events to select/deselect them (visual feedback + Livewire hook).
- Click/drag on slot cells to create time selections (visual overlay + Livewire hook).
- Drag timed events to new time slots (event moves + Livewire hook persists change).
- Drag resize handles to change event duration (event resizes + Livewire hook persists change).
- Override `onEventClick`, `onDateSelect`, `onEventDrop`, `onEventResize` in their calendar subclass to handle interactions.

**What is next (Milestone 6)**:
- Add recurrence (RRULE/EXDATE) and timezone/DST correctness using Luxon + rrule.js.
- Add Pest tests covering recurrence expansion and DST-crossing scenarios.

### Milestone 6 (Completed 2026-02-15)

**What was achieved**:
- Implemented RRULE recurrence expansion using rrule.js with stable parsing via `RRule.parseString()` + `RRuleSet`.
- RRULE contract: accepts `rrule` as either a value-only string (`FREQ=...`) or a full `RRULE:...` line; DTSTART comes from the event `start` value (in calendar zone).
- EXDATE contract: accepts `exdate` as an array of strings; date-only values (`YYYY-MM-DD`) are treated as exclusions at the event's start wall time.
- Occurrence generation: generates recurrence dates in a "UTC-fields" representation via `Date.UTC(...)`, then converts back to Luxon in the calendar zone using the returned date's `getUTC*` components.
- DST-safe slot placement: for timeGrid rendering, computes minutes from wall-clock fields (`hour*60+minute`) so the UI remains consistent on DST transition days while still serializing occurrences with the correct per-date offset.
- Built publishable assets: `resources/dist/livewire-calendar.js` (140.99 kB, gzip: 41.46 kB) and `resources/dist/livewire-calendar.css` (4.56 kB, gzip: 1.19 kB).
- Added Pest Browser tests:
  - `tests/Feature/RecurrenceBrowserTest.php` (3 tests covering RRULE expansion at grid boundaries, EXDATE exclusions, and DST-crossing daily recurrences at correct wall time slots).
  - `tests/Feature/RecurrencePayloadTest.php` (2 tests covering rrule/exdate extra field preservation and timeZone mount prop).
- All tests pass: 112 passed (299 assertions). Zero regressions on existing 107 tests.
- Files modified:
  - `resources/js/livewire-calendar.ts` (added `buildRRuleSet`, `expandRecurringEvents`, `toRRuleUtcFieldsDate`, `fromRRuleUtcFieldsDate` functions; integrated recurrence expansion into event loading)
  - `tests/Feature/RecurrenceBrowserTest.php` (new)
  - `tests/Feature/RecurrencePayloadTest.php` (new)

**What the user can do now**:
- Define recurring events using RRULE strings (e.g., `FREQ=DAILY;COUNT=3`).
- Exclude specific occurrences using EXDATE arrays (e.g., `['2026-05-15']`).
- See recurring events expand correctly across DST transitions, maintaining wall-clock time consistency (e.g., 09:00 America/New_York remains at 09:00 local time before and after DST spring-forward).
- Use any IANA timezone for the calendar and see correct DST handling.

**What is next (Milestone 7)**:
- Add Scheduler-like resources + timeline views (manual equivalents).
- Add Pest tests covering resource rendering and timeline navigation.

## Context and Orientation

This repository is a Laravel package (not a full Laravel app) built on Orchestra Testbench.

Key package files and what they do today:

- `composer.json` defines the package name, PHP/Laravel/Livewire requirements, and dev dependencies for testing.
- `package.json` defines a Vite build used to produce a distributable JS bundle for the package.
- `src/LivewireCalendarServiceProvider.php` registers views/assets via Spatie package-tools and registers a Livewire component alias `livewire-calendar`.
- `src/Livewire/LivewireCalendar.php` is the Livewire component class (currently just renders a view).
- `resources/views/livewire-calendar.blade.php` renders a `wire:ignore` root element `data-livewire-calendar-root` for JS-driven rendering.
- `resources/js/livewire-calendar.ts` is currently a placeholder entrypoint.
- `vite.config.ts` builds an IIFE bundle to `resources/dist/livewire-calendar.js` (and `livewire-calendar.css` if CSS is imported).
- `tests/TestCase.php` is the Testbench test case; it currently registers the Livewire and package service providers.
- `AGENTS.md` contains repo-level guidance, including the requirement that every feature added must have Pest + Pest Browser tests.

Terms used in this plan:

- "Calendar engine": our own JS/Alpine implementation that renders calendar views and places events in the correct positions.
- "View": a calendar presentation mode (month grid, time grid week/day, list, multi-month, resource/timeline).
- "Visible range": the start/end date range currently visible in the active view; used when requesting events/resources.
- "wire:ignore island": a DOM subtree marked `wire:ignore` so Livewire will not morph it; JS owns the DOM inside.

Relevant docs and source references:

- Livewire JS integration and lifecycle events: https://livewire.laravel.com/docs/4.x/javascript and https://livewire.laravel.com/docs/4.x/navigate
- Pest Browser testing: https://pestphp.com/docs/browser-testing
- Spatie package-tools assets/views publishing conventions: `vendor/spatie/laravel-package-tools/README.md` (Assets and Views sections)
- Pest Browser plugin Livewire wrapper behavior: `vendor/pestphp/pest-plugin-browser/src/Api/Livewire.php`

## Plan of Work

The work proceeds in milestones so that (1) the package remains installable, (2) tests drive behavior, and (3) browser-test scaffolding exists before we build complex UI.

Milestone 0 (Tooling and dependency hygiene) makes "no FullCalendar dependency" true in the actual repo and unblocks Playwright so browser tests can run. It also fixes the misleading FullCalendar mention in `composer.json` metadata.

Milestone 1 establishes the public extension contract (consumer-extends-base) and a minimal JS mount that can render something deterministic inside `data-livewire-calendar-root`, plus the first browser test proving the JS bundle loads in a Testbench browser context.

Milestones 2-4 implement the initial core views (month, time grid, list, multi-month) with navigation and basic event rendering.

Milestones 5-7 add interaction parity, recurrence/timezone correctness, and resource/timeline features.

Milestone 8 closes with packaging polish, install ergonomics, and CI.

Throughout, debugging is done with `dump()`, `dd()`, and logs as needed, and all debug statements are removed once the behavior is understood and fixed.

## Concrete Steps

All commands are run from the repository root.

Composer (PHP) tasks:

    composer install
    composer test

Node (JS) tasks (Milestone 0):

    npm install
    npm run playwright:install
    npm run build

Browser tests will be run via Pest:

    ./vendor/bin/pest
    ./vendor/bin/pest --headed   (debug only; not for CI)

## Validation and Acceptance

Global acceptance criteria (must remain true for the full plan):

- No FullCalendar dependencies remain in this repo (at minimum: `package.json` does not include `@fullcalendar/*`; package JS does not import/require FullCalendar).
- `composer test` passes.
- `./vendor/bin/pest` passes, including browser tests, after Playwright is installed.
- Every new feature added in a milestone includes both Pest tests and Pest Browser tests.

Milestone-specific acceptance is defined inside each milestone section below.

## Idempotence and Recovery

- It must be safe to re-run `composer test` and `./vendor/bin/pest` at any time.
- It must be safe to re-run `npm run build`; output is deterministic in `resources/dist/`.
- If browser tests fail due to missing Playwright browsers, re-run `npm run playwright:install`.
- If `npm install` fails due to a local npm auth token issue, remove/refresh npm credentials (do not change the package’s dependency list as a workaround).

## Milestone 0: Tooling, metadata, and dependency hygiene

At the end of this milestone:

- `package.json` contains no `@fullcalendar/*` packages.
- `npm install` succeeds.
- Playwright is installed and browser binaries are installed.
- `npm run build` produces `resources/dist/livewire-calendar.js` (and optionally CSS) suitable for publishing via `php artisan vendor:publish --tag=livewire-calendar-assets`.
- `composer.json` package description no longer claims "FullCalendar-powered".

Work outline:

- Update `package.json` to remove `@fullcalendar/*` devDependencies.
- Add the allowed JS deps for the engine (planned: Luxon + rrule.js) and keep the dependency list minimal.
- Ensure Playwright remains installed as a devDependency (Pest Browser requires it).
- Update `composer.json` `description` to be accurate (no FullCalendar mention).
- Decide and implement the built-asset shipping strategy (recommended for Laravel packages: commit `resources/dist/` so Composer installs do not require Node on consumer machines).

Acceptance checks:

- `npm install` completes without errors.
- `npm run playwright:install` completes without errors.
- `npm run build` creates `resources/dist/livewire-calendar.js`.
- `grep -R "@fullcalendar" package.json` returns no matches.
- `composer test` still passes.

## Milestone 1: Base contracts + minimal JS mount + first browser test

At the end of this milestone:

- The package provides a clear extension contract in PHP for consumers to supply event data for a visible range.
- The JS bundle mounts reliably on pages containing the component, using a Livewire lifecycle event (`livewire:navigated` or `livewire:initialized`) rather than `DOMContentLoaded`.
- The component view includes the published JS/CSS assets (via `asset('vendor/livewire-calendar/...')`), and browser tests can load them in Testbench.
- A Pest Browser test proves the JS bundle loads and initializes (deterministic DOM output or state).

Work outline:

- Define minimal DTO/value objects (storage-agnostic) for the PHP side.
- Define the base Livewire component methods the consumer must override (initially only event loading; resources come later).
- Implement a minimal Alpine component that renders a deterministic placeholder view (no calendar grid yet) and can call `$wire` safely.
- Add a browser-test strategy to serve package assets during browser tests. Prefer setting a test-specific public path (rather than writing into vendor directories).

Acceptance checks:

- New Pest tests for the consumer contract pass.
- New Pest Browser test passes and asserts the JS-initialized marker exists.

## Milestone 2: Month view + navigation + basic events

At the end of this milestone:

- Month grid view renders correctly for a fixed test month.
- Navigation (prev/next/today/goto) updates the visible range and triggers event loading.
- Events returned by the consumer contract render in the correct day cells.

Acceptance checks:

- Pest unit/feature tests for date math (month boundaries) pass.
- Pest Browser tests verify grid structure, navigation behavior, and event rendering.

## Milestone 3: Time grid week/day views

At the end of this milestone:

- Week and day time-grid views render time slots and all-day area.
- Timed events render at correct vertical positions; overlapping events use a simple deterministic layout.

Acceptance checks:

- Pest tests for slot/time calculations pass.
- Pest Browser tests verify timed event placement and overlap layout.

## Milestone 4: List and multi-month views

At the end of this milestone:

- List view renders upcoming events in order for the visible range.
- Multi-month view renders multiple months with navigation.

Acceptance checks:

- Pest Browser tests verify list ordering and multi-month rendering.

## Milestone 5: Interactions

At the end of this milestone:

- Click/select/drag/drop/resize interactions are implemented.
- Livewire methods (consumer-overridable hooks) receive mutation payloads; consumers can persist changes.

Acceptance checks:

- Pest Browser tests cover each interaction.
- Flake mitigation: use stable selectors (`data-testid`) and explicit waits.

## Milestone 6: Recurrence + timezone/DST correctness

At the end of this milestone:

- RRULE and EXDATE recurrence works within a visible range.
- Timezone and DST edge cases are covered by tests.

Acceptance checks:

- Pest tests cover recurrence expansion boundaries.
- Pest Browser tests cover a DST-crossing fixture scenario.

## Milestone 7: Resources + timeline (Scheduler-like)

At the end of this milestone:

- Resources can be loaded and displayed.
- At least one timeline/resource view exists with basic correctness.

Acceptance checks:

- Pest Browser tests cover resource rendering and navigation.

## Milestone 8: Packaging polish + CI

At the end of this milestone:

- README exists and documents install, publishing assets, and consumer extension.
- CI runs: PHP tests, Pint (if added), and browser tests (with Playwright install).

Acceptance checks:

- Fresh install path described in README is executable.

## Artifacts and Notes

Evidence artifacts should be kept small and text-first (avoid screenshots unless explicitly requested). Example artifacts:

- `.sisyphus/evidence/` command outputs showing test passes and key verification checks.

## Interfaces and Dependencies

Required PHP dependencies (already in `composer.json`):

- Laravel 12 (`illuminate/support` + `laravel/framework` in dev).
- Livewire v4.
- Testbench, Pest, pest-plugin-browser.

Required JS tooling (already in `package.json`):

- Vite + TypeScript.
- Playwright (devDependency) for Pest Browser.

Allowed JS runtime/build deps (to be added in Milestone 0):

- Luxon (date/time and IANA zones) for correct timezone/DST handling.
- rrule.js (recurrence rules) for RRULE/EXDATE support.

Explicitly forbidden:

- Any dependency on FullCalendar packages/code (`@fullcalendar/*` or similar).

Initial PHP extension contract (to be finalized in Milestone 1):

- A base Livewire component class that consumers extend, providing an event-loading method that accepts a visible date range and returns a list of event DTOs.
