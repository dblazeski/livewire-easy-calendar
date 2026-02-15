# Learnings

Append-only. Capture useful patterns and verified facts.

## 2026-02-14: Milestone 0 npm/Playwright unblocked

- Removing `@fullcalendar/*` from `package.json` allowed `npm install` to succeed.
- Playwright installed successfully; `./node_modules/.bin/playwright run-server --version` returned `Version 1.58.2` (>= pest-plugin-browser minimum 1.54.1).
- `npm run build` now produces `resources/dist/livewire-calendar.js` (currently an empty chunk because `resources/js/livewire-calendar.ts` is a placeholder).
- Evidence captured at `.sisyphus/evidence/livewire-calendar-engine-m0.txt`.

## 2026-02-14: Milestone 1A — Event extension contract

- **Contract**: `LivewireCalendar::fetchEvents(string $startIso, string $endIso): array` is the public Livewire action. It accepts ISO-8601 strings (Livewire-safe primitives), converts them internally to a `DateRange` value object, then delegates to `protected function events(DateRange $range): array`.
- **Consumer extension**: Subclass `LivewireCalendar`, override `events(DateRange $range)`. Return plain arrays or `CalendarEvent` objects; `fetchEvents` normalizes both to plain arrays.
- **Value objects**: `DateRange` (readonly, `Carbon $start`/`$end`, factory `fromIso()`) and `CalendarEvent` (readonly, `$id`/`$title`/`$start`/`$end`/`$extra`, `toArray()` serializes to ISO strings and spreads `$extra`).
- **Testing**: `Livewire::test(...)->call('fetchEvents', ...)->assertReturned(...)` works. `assertReturned` accepts a callable for custom assertions (returns `true`/`false`) or a value for equality.
- **TestCase**: Livewire component tests require `APP_KEY`; set via `defineEnvironment()` in the Testbench TestCase.
- **Autoloading**: The `classmap` entry in `composer.json` requires `composer dump-autoload` when adding new classes to `src/`.

## 2026-02-14: Milestone 1B — JS/CSS assets in browser tests

- **Asset serving in browser tests**: The Pest Browser plugin's `LaravelHttpServer` serves static files from `public_path()`. In Testbench, this resolves to the testbench skeleton's `public/` directory. To make package assets available, `TestCase::setUp()` copies `resources/dist/*` into `public_path('vendor/livewire-calendar/')` using `Illuminate\Filesystem\Filesystem::copyDirectory()`.
- **Browser test detection**: `BrowserTestIdentifier::isBrowserTest()` detects browser tests by tokenizing the test closure source and looking for `visit(` tokens, OR by checking if the test file is in `tests/Browser/`. `Livewire::test()` (from `Pest\Browser\Api\Livewire`) calls `visit()` internally, but the detection scans only the test's own source code — so `Livewire::test()` is NOT detected as a browser test when used in `tests/Feature/`. Workaround: use `visit()` directly with a route that renders the Livewire component via `Blade::render()`.
- **Vite CSS output**: Importing a `.css` file from the TS entrypoint (`import './livewire-calendar.css'`) causes Vite to emit a separate CSS asset file alongside the JS bundle, controlled by `rollupOptions.output.assetFileNames` in `vite.config.ts`.
- **JS initialization pattern**: The JS entrypoint immediately scans for `[data-livewire-calendar-root]` elements and sets `data-livewire-calendar-initialized="true"`. It also registers `document.addEventListener('livewire:navigated', ...)` for re-scanning after Livewire navigation. Skip-if-already-initialized prevents double-init.
- **AwaitableWebpage auto-retry**: Pest Browser's `AwaitableWebpage` wraps every assertion with `Execution::waitForExpectation()`, which retries until the assertion passes or times out. No explicit `waitFor` or `sleep` needed — `assertAttribute` inherently waits.

## 2026-02-14: Milestone 2A — Month view grid algorithm

- **Grid algorithm**: Mirrors FullCalendar's `fixedWeekCount: true` (6 rows) and `showNonCurrentDates: true` defaults. Always renders exactly 42 cells (6×7).
- **Grid start calculation**: `monthStart = initialDate.startOf('month')`. Convert Luxon weekday (1=Mon..7=Sun) to JS weekday (0=Sun..6=Sat) via `jsWeekday = weekday % 7`. Then `daysBack = (jsWeekday - firstDay + 7) % 7`. `gridStart = monthStart.minus({ days: daysBack })`.
- **Weekday header reordering**: Luxon `Info.weekdays('short')` returns Monday-first. Rotate to match `firstDay` via `luxonStartIndex = (firstDay + 6) % 7`, then slice and concat.
- **Data flow**: Livewire `mount()` sets `initialDate` (YYYY-MM-DD, defaults to today) and `firstDay` (0-6, defaults to 0=Sunday). Blade emits these as `data-livewire-calendar-initial-date` and `data-livewire-calendar-first-day` on the root element. JS reads them at init time.
- **Title format**: `monthStart.toFormat('LLLL yyyy')` produces standalone month name + year (e.g., "May 2026").
- **Stable test selectors**: `data-testid="calendar-title"`, `data-testid="month-grid"`, `data-testid="day-cell-YYYY-MM-DD"`, `data-date="YYYY-MM-DD"`, `data-current-month="true|false"`.
- **Luxon bundled into IIFE**: Vite's IIFE lib build bundles Luxon directly into the output JS (~73KB). No external dependency needed at runtime.

## 2026-02-14: Milestone 2B — Month navigation with deterministic today

- **Deterministic today mechanism**: The Livewire component accepts an optional `today` prop (string, `Y-m-d`). If not provided, `mount()` initializes it to `now()->format('Y-m-d')`. The Blade template emits it as `data-livewire-calendar-today` on the root element. The JS reads this attribute once per render cycle and uses it as the target for the "Today" button — never calling `DateTime.now()` for navigation. This makes the Today button fully deterministic and testable: browser tests pass `today="2026-01-15"` and assert clicking Today shows "January 2026".
- **Re-render strategy**: `renderCalendar(root, monthStart, firstDay)` clears all children of the root element (preserving root attributes like `data-livewire-calendar-today`, `wire:ignore`, etc.) then rebuilds toolbar → title → grid. Navigation button click handlers call `renderCalendar` recursively with adjusted `monthStart`. No external state tracking needed — the current month is captured in the closure.
- **Toolbar selectors**: `data-testid="btn-prev"`, `data-testid="btn-today"`, `data-testid="btn-next"`, `data-testid="calendar-toolbar"`.
- **Browser test pattern for clicks**: Pest Browser's `click('[selector]')` triggers the JS click handler, and `assertSeeIn` auto-retries until the DOM updates. No explicit `waitFor` or `sleep` needed for purely client-side re-renders.

## 2026-02-14: Milestone 2C — Event loading and rendering in Month view

- **Event fetch integration**: JS calls `$wire.$call('fetchEvents', startStr, endStr)` after each `renderCalendar` call. The `$wire` object is obtained via `window.Livewire.find(wireId)` where `wireId` comes from `root.closest('[wire\\:id]').getAttribute('wire:id')`.
- **Two-phase initialization**: On initial page load, the inline script runs before Livewire initializes components. `renderCalendar` builds the grid immediately and attempts `loadEvents`, which silently no-ops if `Livewire.find()` returns undefined. A `livewire:initialized` listener retries event loading once Livewire finishes booting. On subsequent navigations (prev/next/today clicks), Livewire is already available so `loadEvents` succeeds directly from `renderCalendar`.
- **Stale fetch prevention**: A per-root render token (`WeakMap<HTMLElement, number>`) increments on each `renderCalendar` call. When the async `$wire.$call` resolves, it checks if the token still matches; stale results (from a navigation that occurred mid-fetch) are silently discarded.
- **Visible range computation**: `gridStart.toFormat('yyyy-MM-dd')` to `gridStart.plus({ days: 42 }).toFormat('yyyy-MM-dd')` — deterministic YYYY-MM-DD strings, no client timezone shifting.
- **Event placement**: Each event's `start` ISO string is parsed by Luxon and formatted to `yyyy-MM-dd`, then matched to `grid.querySelector('[data-date="..."]')`. A `<div class="lec-event" data-event-id="...">` is appended to the matching cell.
- **Day cell structure change**: Day cells now wrap the day number in `<span class="lec-day-number">` instead of bare `textContent`, allowing event elements to be appended as siblings below the number. Existing tests (which assert `data-testid`, `data-date`, `data-current-month` attributes and text presence via `assertSeeIn`) remain unaffected.
- **Browser test pattern for Livewire calls**: Named test component classes (extending `LivewireCalendar`) must be defined at file scope and registered via `Livewire::component('name', Class::class)` in `beforeEach`. This allows `<livewire:name ...>` in `Blade::render()`. Pest Browser's auto-retry (`waitForExpectation`) naturally waits for the async `fetchEvents` round-trip to complete before assertions pass.

## 2026-02-14: Milestone 3A — View prop and timeGridWeek skeleton

- **View prop**: Livewire component accepts `?string $view = null` in `mount()`, defaults to `'month'`. Blade emits `data-livewire-calendar-view="{{ $view }}"` on the root element. JS reads `data-livewire-calendar-view` at init and passes it through all `renderCalendar` calls.
- **View routing**: `renderCalendar(root, anchorDate, firstDay, view)` builds the shared toolbar then branches: `'month'` delegates to `renderMonthView`, `'timeGridWeek'` delegates to `renderTimeGridWeek`. Navigation buttons are view-aware: month moves ±1 month, week moves ±7 days.
- **Week start computation**: `computeWeekStart(date, firstDay)` reuses `computeGridStart(date.startOf('day'), firstDay)`. Same formula as month grid start: `jsWeekday = date.weekday % 7; daysBack = (jsWeekday - firstDay + 7) % 7; return date.minus({ days: daysBack })`.
- **Week range**: 7 consecutive days from weekStart. For `initialDate="2026-05-14"` (Thursday) with `firstDay=0` (Sunday): weekStart=2026-05-10, weekEnd=2026-05-16.
- **FullCalendar reference defaults used**: slotDuration=30min (48 time slots per day), slotMinTime=00:00, slotMaxTime=24:00, allDaySlot=true. scrollTime not implemented in skeleton.
- **TimeGrid DOM structure**: `[data-testid="timegrid"]` container wraps: (1) header row with gutter + 7 day column headers `[data-testid="timegrid-day-YYYY-MM-DD"]`, (2) all-day row `[data-testid="allday-row"]` with gutter + 7 cells, (3) scrollable body with 48 slot rows, each containing a time label (hour labels get `[data-testid="time-label-HH:00"]`) + 7 slot cells.
- **Title format**: Week view title shows date range: same month="LLLL d – d, yyyy", cross-month="LLL d – LLL d, yyyy", cross-year="LLL d, yyyy – LLL d, yyyy".
- **Today button in week view**: Computes `computeWeekStart(todayDate, firstDay)` where `todayDate` comes from `data-livewire-calendar-today` attribute (deterministic).
- **No event loading in week view**: `renderTimeGridWeek` accepts `_token` but does not call `loadEvents`. The `livewire:initialized` handler still only looks for `[data-testid="month-grid"]` and skips week views.
- **Existing month tests preserved**: All 27 pre-existing tests pass unchanged. The refactor only extracted month rendering into `renderMonthView` function — semantics identical.

## 2026-02-14: Milestone 3B — timeGridDay skeleton and day range math

- **Day range math**: Day view anchor is simply `initialDate.startOf('day')`. Navigation: prev = `anchorDate.minus({ days: 1 })`, next = `anchorDate.plus({ days: 1 })`, today = `todayDate.startOf('day')`. No week-start computation needed — the anchor IS the single displayed day.
- **Shared timegrid renderer**: Extracted `renderTimeGrid(root, days: DateTime[])` that accepts an array of days and renders the full timegrid structure (header + allday row + 48 time slots) with `days.length` columns. Both `renderTimeGridWeek` (7 days) and `renderTimeGridDay` (1 day) delegate to this helper, eliminating duplication.
- **Dynamic column count via CSS custom property**: The timegrid container sets `--lec-day-count` inline style. CSS uses `grid-template-columns: 3.75rem repeat(var(--lec-day-count), 1fr)` on `.lec-timegrid-header`, `.lec-allday-row`, and `.lec-timegrid-slot`. Default is 7 (week). Day view sets it to 1. No separate CSS classes needed — week view is unchanged.
- **Day view title format**: `dayDate.toFormat('LLLL d, yyyy')` → "May 14, 2026".
- **Stable selectors reused**: `data-testid="timegrid"`, `data-testid="allday-row"`, `data-testid="time-label-HH:00"` are identical between day and week views. Day column uses `data-testid="timegrid-day-YYYY-MM-DD"` with a single entry.
- **All 47 tests pass**: 10 new day view tests + 37 existing (month, week, events, smoke, service provider, autoload).

## 2026-02-14: Milestone 3C — Timed event rendering in timeGrid views

- **DOM structure for timed events**: The timegrid body now wraps slot rows in a `.lec-timegrid-body-inner` (position: relative) container. An overlay `.lec-timegrid-events-layer` is positioned absolutely (top: 0, bottom: 0, left: 3.75rem, right: 0) to span the full slot height while skipping the time-label gutter. It uses `display: grid; grid-template-columns: repeat(var(--lec-day-count), 1fr)` to align with day columns. Each day gets a `.lec-timegrid-day-body` (position: relative, `data-testid="timegrid-day-body-YYYY-MM-DD"`, `data-date="YYYY-MM-DD"`) for hosting absolutely positioned events.
- **Event placement math**: Each event is segmented per visible day. For each day segment, `startMin = segStart.diff(dayStart, 'minutes').minutes` and `endMin = segEnd.diff(dayStart, 'minutes').minutes`, clamped to 0..1440 by clamping segment start/end to day boundaries. Position uses percentage: `top = (startMin/1440)*100%`, `height = ((endMin-startMin)/1440)*100%`. Tests assert `data-start-min` and `data-end-min` attributes, not pixel offsets.
- **Midnight-spanning events**: Events crossing midnight are split into per-day segments. The event start/end is compared against each day's [dayStart, dayEnd) interval. If the event overlaps, the segment is clamped to the day boundaries. Each segment gets its own DOM element with `data-testid="timed-event-<id>-<YYYY-MM-DD>"`.
- **Stale-fetch token**: `loadTimeGridEvents` uses the same `renderTokens` WeakMap as month view. The token is checked both before the `$wire.$call` and after the async result returns, preventing stale event rendering after navigation.
- **`livewire:initialized` handler**: Updated to dispatch based on `data-livewire-calendar-view` attribute. Month view: existing logic (find month-grid, loadEvents). TimeGrid views: find `.lec-timegrid-events-layer`, extract days from `[data-date]` attributes on day-body elements, call `loadTimeGridEvents`.
- **`renderTimeGrid` now returns HTMLElement**: Returns the events layer element so callers (`renderTimeGridWeek`, `renderTimeGridDay`) can pass it to `loadTimeGridEvents`.
- **`renderTimeGridDay` signature changed**: Now accepts `token: number` parameter (previously had no token), enabling stale-fetch prevention.
- **CSS additions**: `.lec-timegrid-body-inner` (position: relative), `.lec-timegrid-events-layer` (absolute overlay with pointer-events: none), `.lec-timegrid-day-body` (relative container), `.lec-timed-event` (absolute positioned, full-width within day column, pointer-events: auto, z-index: 1).
- **All 56 tests pass**: 9 new timed-event tests + 47 existing.

## 2026-02-14: Milestone 3D — All-day event rendering and timed-event overlap layout

- **All-day detection rule**: An event is treated as all-day when its serialized payload contains `allDay === true` (strict equality). This value comes from PHP `CalendarEvent::$extra` array being spread into the top-level serialized object via `toArray()`. Example: `new CalendarEvent(..., extra: ['allDay' => true])` produces `{ ..., allDay: true }` in JSON.
- **All-day cell data attributes**: Each all-day cell now has `data-testid="allday-cell-YYYY-MM-DD"` and `data-date="YYYY-MM-DD"` for targeting in tests. The loop changed from `for (let i = 0; i < dayCount; i++)` to `for (const day of days)` to access each day's date string.
- **All-day event DOM**: All-day events are rendered as `<div class="lec-allday-event" data-testid="allday-event-<id>-<YYYY-MM-DD>" data-event-id="<id>" data-date="YYYY-MM-DD">` inside the matching allday cell. Multi-day all-day events produce one element per visible day, using the same day-overlap check as timed events (`eventStart >= dayEnd || eventEnd <= dayStart` → skip).
- **Overlap algorithm**: Greedy column packing with deterministic sorting. For each day column: (1) Collect all timed segments for that day. (2) Sort by `startMin` ascending, then duration descending (longer events first), then `event.id` lexicographic (tiebreaker). (3) Walk sorted list to build overlap groups: an event joins the current group if its `startMin < groupEnd`; otherwise a new group starts. (4) Within each group, greedily assign columns: find the first column where `seg.startMin >= colEnd[c]`; if none, create a new column. (5) All events in a group share the same `colCount` = total columns used.
- **Determinism guarantees**: The sort order (startMin asc → duration desc → id asc) ensures identical column assignments for identical input, regardless of the order events arrive from PHP. No randomness or hash-based decisions.
- **Overlap layout CSS**: `left = (col / colCount) * 100%`, `width = (1 / colCount) * 100%` set as inline styles. CSS `.lec-timed-event` no longer has `right: 0`; instead uses `box-sizing: border-box` so padding doesn't overflow the computed width. Non-overlapping events get `left: 0%; width: 100%` (functionally identical to the old `left:0; right:0`).
- **New data attributes on timed events**: `data-col` (0-based column index) and `data-col-count` (total columns in the overlap group). These enable reliable Pest Browser assertions on overlap layout without inspecting computed CSS.
- **Idempotent rendering**: `renderTimeGridEvents` now clears all `.lec-timed-event` elements from the events layer and all `.lec-allday-event` elements from the allday row before rendering. This prevents duplicate DOM nodes if `loadTimeGridEvents` is invoked twice for the same view (e.g., from both `renderCalendar` and `livewire:initialized`).
- **Function signature changes**: `renderTimeGrid` now returns `{ eventsLayer: HTMLElement; alldayRow: HTMLElement }` instead of just `HTMLElement`. `loadTimeGridEvents` and `renderTimeGridEvents` both accept an `alldayRow: HTMLElement` parameter. The `livewire:initialized` handler now looks up `[data-testid="allday-row"]` alongside `.lec-timegrid-events-layer`.
- **New CSS**: `.lec-allday-event` styled identically to `.lec-event` (month view events). `.lec-allday-cell` gained `padding: 0.125rem 0.25rem` for breathing room.
- **All 69 tests pass**: 13 new tests (8 all-day + 5 overlap) + 56 existing. Zero regressions.

## 2026-02-14: Milestone 4 — List view and Multi-month year view

- **View names**: `listWeek` (list view for a 7-day week) and `multiMonthYear` (12-month year grid). These follow FullCalendar's canonical view naming conventions.
- **listWeek visible range**: Same as `timeGridWeek` — 7 consecutive days from `computeWeekStart(initialDate, firstDay)`. Fetch range: `[weekStart, weekStart + 7 days)`.
- **listWeek navigation**: prev/next moves ±7 days; today jumps to `computeWeekStart(todayDate, firstDay)`. Reuses same navigation logic as `timeGridWeek`.
- **listWeek event sorting**: Events sorted globally by `start` ascending, then `end` ascending, then `id` lexicographic ascending. Sorting uses Luxon `DateTime.fromISO()` comparisons. Events spanning multiple days appear in each day group they overlap.
- **listWeek DOM structure**: `[data-testid="list-view"]` container with `data-range-start` and `data-range-end` attributes (used by `livewire:initialized` handler for retry). Each day with events gets `[data-testid="list-day-YYYY-MM-DD"]` group containing a heading and event rows. Each event row: `[data-testid="list-event-<id>"]` with `data-event-id` and `data-date`. Events show time range (HH:mm – HH:mm) or "all-day" label.
- **listWeek deduplication**: Within a day group, events are deduplicated by id to prevent multi-day events from appearing twice in the same day group.
- **multiMonthYear visible range**: Full year from `initialDate.startOf('year')`. No event fetching — structural rendering only.
- **multiMonthYear navigation**: prev/next moves ±1 year; today jumps to `todayDate.startOf('year')`.
- **multiMonthYear DOM structure**: `[data-testid="multimonth-view"]` container. Each month: `[data-testid="multimonth-month-YYYY-MM"]` with a visible month title (`monthStart.toFormat('LLLL')`). Each month contains a 42-cell grid (same algorithm as month view) with `[data-date]` and `[data-current-month]` attributes on each cell.
- **multiMonthYear CSS layout**: 3-column CSS grid (`grid-template-columns: repeat(3, 1fr)`) with 1.5rem gap. Compact day cells use 0.625rem font size.
- **Title formats**: listWeek uses same date range format as timeGridWeek. multiMonthYear uses just the year (`yearStart.toFormat('yyyy')`).
- **livewire:initialized handler**: Updated to handle `listWeek` view — finds `[data-testid="list-view"]`, reads `data-range-start` attribute, and calls `loadListEvents` to retry event fetching after Livewire boots. `multiMonthYear` and `month` (default) views need no special handler since multiMonth has no events.
- **Pest Browser API**: `Webpage::script()` exists but combining it with `assertAttribute()` causes timeout issues because `assertAttribute` auto-retries but the script only runs once. Use CSS nth-child selectors for DOM order assertions instead.
- **All 93 tests pass**: 24 new tests (13 list + 11 multi-month) + 69 existing. Zero regressions.

## 2026-02-14: Milestone 5 — Interactions (click/select/drag/drop/resize)

### Stable selectors added
- **Slot cells**: `data-testid="slot-cell-YYYY-MM-DD-HH:MM"` with `data-date="YYYY-MM-DD"` and `data-minute="<total-minutes>"` (e.g., 540 for 09:00).
- **Selection overlay**: `data-testid="timegrid-selection"` with `data-start="YYYY-MM-DDTHH:mm:ss"` and `data-end="YYYY-MM-DDTHH:mm:ss"` (no timezone offset — local ISO format).
- **Resize handles**: `data-testid="timed-event-resize-handle-<id>-<YYYY-MM-DD>"` inside each `.lec-timed-event`.
- **Timed event attributes**: `data-event-start` and `data-event-end` (full ISO with timezone offset from PHP, e.g., `2026-05-14T09:00:00+00:00`).

### Livewire action names (public methods on LivewireCalendar)
- `eventClick(string $eventId, array $eventData)` → delegates to `onEventClick(string $eventId, array $eventData): void`
- `dateSelect(string $startIso, string $endIso, bool $allDay)` → delegates to `onDateSelect(string $startIso, string $endIso, bool $allDay): void`
- `eventDrop(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd)` → calls `onEventDrop`, then returns `fetchEvents($rangeStart, $rangeEnd)` for client re-render
- `eventResize(string $eventId, string $newStart, string $newEnd, string $rangeStart, string $rangeEnd)` → calls `onEventResize`, then returns `fetchEvents($rangeStart, $rangeEnd)` for client re-render

### JS interaction architecture
- **Global event delegation**: Single `mousedown`/`mousemove`/`mouseup`/`click` listeners on `document`. Mousedown dispatches to `startResize` (if target is `.lec-resize-handle`), `startDrag` (if target is inside `.lec-timed-event`), or `startSelect` (if target is `.lec-slot-cell`).
- **State variables**: `pendingDrag`, `pendingResize`, `pendingSelect` (nullable objects), `interactionMoved` (boolean), `suppressNextClick` (boolean flag to prevent click after drag/resize).
- **`RootContext` WeakMap**: Stores `{ eventsLayer, alldayRow, days }` per root element so interaction handlers can call `renderTimeGridEvents` after drop/resize without re-querying the DOM.
- **Render token stale protection**: Drop/resize handlers capture the current render token before calling `$wire.$call()` and check it after the async response. If the token changed (user navigated), the stale response is discarded.

### Playwright actionability vs pointer-events: none
- The `.lec-timegrid-events-layer` has `pointer-events: none` and is absolutely positioned over slot cells. Despite `pointer-events: none`, Playwright's actionability check considers slot cells as obscured by the overlay and times out on `click()` or `drag()`.
- **Workaround**: Use `Webpage::script()` to dispatch `MouseEvent('mousedown', { bubbles: true })` + `MouseEvent('mouseup', { bubbles: true })` directly on the slot cell element. This bypasses Playwright's hit-test while correctly triggering the document-level event delegation.
- Timed events (`pointer-events: auto`, z-index above events layer) pass Playwright's actionability check — `click()` and `drag()` work directly on them.

### Click suppression after drag/resize
- A time-based guard (`Date.now() - lastInteractionTime < 100`) was too aggressive — it suppressed legitimate clicks on different elements within the 100ms window.
- Replaced with a deterministic `suppressNextClick` boolean flag: set to `true` in the `mouseup` handler only when `pendingDrag && interactionMoved` or `pendingResize && interactionMoved`. The `click` handler checks the flag, resets it, and returns early if set. This prevents the spurious click that browsers fire after mousedown+mouseup on the same element during a drag, without affecting subsequent independent clicks.
- Select interactions (slot cell mousedown/mouseup) do NOT set `suppressNextClick` because the click event after a slot cell interaction targets the slot cell (not a timed event), so the click handler's `.closest('.lec-timed-event')` check naturally filters it out.

### Test patterns for interactions
- **Timed event click**: `->click('[data-testid="timed-event-<id>-<date>"]')` works directly (Playwright can reach it).
- **Slot cell click**: `$page->script("...dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))...")` then `$page->assertPresent(...)`.
- **Drag/drop**: `->drag('[data-testid="timed-event-<id>-<date>"]', '[data-testid="slot-cell-<date>-<time>"]')` works because source is a timed event (actionable).
- **Resize**: `->drag('[data-testid="timed-event-resize-handle-<id>-<date>"]', '[data-testid="slot-cell-<date>-<time>"]')` works because source is a resize handle inside a timed event (actionable).
- **Test component pattern**: Define a test calendar class with public `$evt1Start`/`$evt1End` properties. Override `onEventDrop`/`onEventResize` to update these properties. `events()` reads from the properties, so `fetchEvents` returns updated positions after drop/resize. This allows browser tests to assert the new event position after a Livewire round-trip.

### All 107 tests pass
- 14 new tests (10 browser + 4 PHP hooks) + 93 existing. Zero regressions. 284 assertions total.
## 2026-02-14: Milestone 6 — Recurrence (RRULE/EXDATE) and DST correctness

### RRULE string format with TZID
- **DTSTART with TZID**: When the event has a timezone, we build the RRULE string with `DTSTART;TZID=${zone}:${dtstart.toFormat("yyyyMMdd'T'HHmmss")}` followed by the RRULE line. This tells rrule.js to interpret the recurrence in the specified timezone.
- **EXDATE with TZID**: Similarly, exclusion dates use `EXDATE;TZID=${zone}:${exDt.toFormat("yyyyMMdd'T'HHmmss")}` format.
- **Multi-line format**: The full RRULE string is built by joining lines with `\n`, e.g., `DTSTART;TZID=America/New_York:20260307T090000\nRRULE:FREQ=DAILY;COUNT=3\nEXDATE;TZID=America/New_York:20260308T090000`.

### Date conversion for rrule.js
- **toRRuleUtcFieldsDate**: Converts Luxon `DateTime` to JS `Date` by storing the wall-clock time fields (year, month, day, hour, minute, second) in UTC fields via `Date.UTC()`. This is used for the `between()` range parameters.
- **fromRRuleUtcFieldsDate**: Converts JS `Date` returned by rrule.js back to Luxon `DateTime`. When using `DTSTART;TZID=...`, rrule.js returns **true UTC timestamps** (not wall-clock times stored in UTC fields). The conversion uses `DateTime.fromJSDate(date, { zone: 'UTC' }).setZone(zone)` to correctly interpret the UTC timestamp and convert it to the target timezone.

### DST handling
- **Wall-clock time preservation**: When an event recurs daily at 09:00 in America/New_York, it should appear at 09:00 local time on every occurrence, even across DST transitions (e.g., 2026-03-08 when clocks spring forward from 02:00 to 03:00).
- **rrule.js with TZID handles DST**: By using `DTSTART;TZID=America/New_York:...`, rrule.js automatically adjusts for DST. The returned UTC timestamps represent the correct wall-clock time in the specified timezone.
- **Luxon conversion**: `DateTime.fromJSDate(date, { zone: 'UTC' }).setZone(zone)` preserves the underlying instant while displaying it in the target timezone. This ensures that 09:00 America/New_York on 2026-03-07 (UTC-05:00) and 09:00 America/New_York on 2026-03-08 (UTC-04:00, after DST) both render at `data-start-min="540"` (09:00 wall-clock time).

### Occurrence ID format
- **Timed events**: `${event.id}__${occStart.toFormat("yyyyMMdd'T'HHmmss")}` (e.g., `dst-evt__20260307T090000`).
- **All-day events**: `${event.id}__${occStart.toFormat('yyyyMMdd')}` (e.g., `all-day-evt__20260514`).

### All 112 tests pass
- 3 new recurrence tests (1 boundary, 1 EXDATE, 1 DST) + 109 existing. Zero regressions. 299 assertions total.

### DST test verification (2026-02-14)
- **Test scenario**: Daily recurrence at 09:00 America/New_York crossing DST boundary (2026-03-08 02:00→03:00).
- **Expected behavior**: Event appears at 09:00 wall-clock time on all three days (2026-03-07, 2026-03-08, 2026-03-09), with `data-start-min="540"` on each occurrence.
- **Implementation correctness**: The existing implementation already handles DST correctly:
  1. `buildRRuleSet` uses `DTSTART;TZID=${zone}:...` format, which tells rrule.js to interpret recurrences in the specified timezone.
  2. rrule.js returns true UTC timestamps that represent the correct wall-clock time in the target timezone.
  3. `fromRRuleUtcFieldsDate` converts these UTC timestamps to Luxon DateTime in the target zone via `DateTime.fromJSDate(date, { zone: 'UTC' }).setZone(zone)`.
  4. `wallClockMinuteInDayGrid` extracts the wall-clock hour/minute from the Luxon DateTime, ensuring consistent `data-start-min` values across DST transitions.
- **Test result**: All assertions pass. The DOM nodes exist with correct `data-testid` and `data-start-min="540"` attributes for all three occurrences.

## 2026-02-15: Milestone 6 implementation update (stable rrule parsing + DST-safe slot math)

- **Stable rrule parsing**: Use `RRule.parseString()` + `RRuleSet` instead of relying on `rrulestr()` overrides/parse modes.
- **RRULE contract**: Accept `rrule` as either a value-only string (`FREQ=...`) or a full `RRULE:...` line; DTSTART comes from the event `start` value (in calendar zone).
- **EXDATE contract**: Accept `exdate` as an array of strings; date-only values (`YYYY-MM-DD`) are treated as exclusions at the event's start wall time.
- **Occurrence generation**: Generate recurrence dates in a "UTC-fields" representation via `Date.UTC(...)`, then convert back to Luxon in the calendar zone using the returned date's `getUTC*` components.
- **DST-safe slot placement**: For timeGrid rendering, compute minutes from wall-clock fields (`hour*60+minute`) so the UI remains consistent on DST transition days while still serializing occurrences with the correct per-date offset.

## 2026-02-15: Milestone 6 verification (DST test already passing)

- **Discovery**: The DST test was already passing when Milestone 6 work began. The existing implementation correctly handles DST transitions.
- **Root cause analysis**: The current `fromRRuleUtcFieldsDate` implementation uses `DateTime.fromObject({ ...getUTC* fields }, { zone })`, which interprets the UTC fields as wall-clock time in the target zone. This works correctly because:
  1. `toRRuleUtcFieldsDate` stores wall-clock time fields (year, month, day, hour, minute) in UTC fields via `Date.UTC()`.
  2. rrule.js operates on these "floating" UTC dates and returns Date objects with the same UTC-field representation.
  3. `fromRRuleUtcFieldsDate` extracts the UTC fields and interprets them as wall-clock time in the calendar zone via `DateTime.fromObject({ ...fields }, { zone })`.
  4. Luxon automatically applies the correct DST offset for each occurrence date when creating the DateTime in the target zone.
- **Wall-clock time preservation**: When an event recurs daily at 09:00 America/New_York, the UTC fields (hour=9) are preserved across DST transitions. Luxon's `DateTime.fromObject` applies the correct offset for each date:
  - 2026-03-07 09:00 America/New_York → UTC-05:00 (before DST)
  - 2026-03-08 09:00 America/New_York → UTC-04:00 (after DST spring-forward at 02:00)
  - 2026-03-09 09:00 America/New_York → UTC-04:00 (after DST)
- **Slot rendering**: `wallClockMinuteInDayGrid` extracts `dt.hour * 60 + dt.minute` from the Luxon DateTime, which gives the wall-clock time (09:00 = 540 minutes) regardless of the underlying UTC offset. This ensures consistent `data-start-min="540"` across DST transitions.
- **Test verification**: All 112 tests pass, including the DST test that asserts `data-start-min="540"` for all three occurrences (2026-03-07, 2026-03-08, 2026-03-09).
- **Conclusion**: No code changes were needed for Milestone 6. The existing implementation already handles RRULE expansion and DST transitions correctly. The "floating UTC fields" approach (storing wall-clock time in UTC fields, then interpreting them in the target zone) is the correct pattern for rrule.js integration with Luxon.
## 2026-02-15: Milestone 7 (part 1) — Resources extension contract

- **Contract**: `LivewireCalendar::fetchResources(string $startIso, string $endIso): array` is the public Livewire action. It accepts ISO-8601 strings (Livewire-safe primitives), converts them internally to a `DateRange` value object, then delegates to `protected function resources(DateRange $range): array`.
- **Consumer extension**: Subclass `LivewireCalendar`, override `resources(DateRange $range)`. Return plain arrays or `CalendarResource` objects; `fetchResources` normalizes both to plain arrays.
- **Value object**: `CalendarResource` (readonly, `$id`/`$title`/`$extra`, `toArray()` serializes and spreads `$extra`). Minimal and storage-agnostic, similar to `CalendarEvent` but without date fields.
- **Payload shape**: Resources are serialized as `{ id: string, title: string, ...extra }`. The `extra` array allows consumers to pass arbitrary metadata (e.g., `capacity`, `location`, `color`) without modifying the core contract.
- **Testing**: `Livewire::test(...)->call('fetchResources', ...)->assertReturned(...)` works identically to `fetchEvents`. Tests verify default empty array, plain array returns, `CalendarResource` normalization, date range passing, extra field preservation, and mixed array/object handling.
- **Symmetry with events contract**: The resources contract mirrors the events contract exactly: same `DateRange::fromIso()` conversion, same `array_map()` normalization pattern, same test structure. This consistency makes the API predictable for consumers.

## 2026-02-15: Milestone 7 (part 2) — `resourceTimelineDay` selectors + payload assumptions

- **View name**: Use `view="resourceTimelineDay"` to render the resources + single-day timeline view.
- **Livewire calls**: JS loads both resources and events for the visible day via `$wire.$call('fetchResources', rangeStart, rangeEnd)` and `$wire.$call('fetchEvents', rangeStart, rangeEnd)`.
- **Event payload assumption**: Events intended for the resource timeline must include a top-level `resourceId` field (string or number). JS matches this against `resource.id` (stringified) to choose the row.
- **Stable selectors**:
  - Container: `data-testid="resource-timeline"`
  - Resource row: `data-testid="resource-row-<resourceId>"` + `data-resource-id="<resourceId>"`
  - Resource label: `data-testid="resource-label-<resourceId>"`
  - Resource event: `data-testid="resource-event-<eventId>-<resourceId>"` + `data-start-min` / `data-end-min`

## 2026-02-15: Milestone 7 (part 2A) — Resource timeline resources-only skeleton

- **Livewire call**: In `resourceTimelineDay`, JS loads resources for a single-day visible range via `$wire.$call('fetchResources', rangeStart, rangeEnd)`.
- **Range computation**: `rangeStart = anchorDate.startOf('day')` and `rangeEnd = rangeStart.plus({ days: 1 })`, both computed in the calendar time zone from `data-livewire-calendar-time-zone`. Strings passed to Livewire are `YYYY-MM-DD`.
- **Boot timing**: Initial `renderCalendar()` may run before Livewire is ready; a `livewire:initialized` handler re-invokes the resource loader for `resourceTimelineDay` using the timeline container's `data-date`.
- **Stable selectors (resources)**:
  - Container: `data-testid="resource-timeline"`
  - Row: `data-testid="resource-row-<id>"` + `data-resource-id="<id>"`
  - Label: `data-testid="resource-label-<id>"`

## 2026-02-15: Milestone 7 (part 2B) — Resource timeline event rendering + recurrence

- **Event payload shape (confirmed)**: `CalendarEvent::$extra` is spread to the top-level event payload, so `extra: ['resourceId' => 'res-1']` serializes to `{ ..., resourceId: 'res-1' }` (not nested).
- **Resource lane targeting**: JS renders into `.lec-resource-timeline-lane[data-resource-id="<resourceId>"]` and matches `event.resourceId` to `resource.id` using string comparison.
- **Stable selectors (events)**: Each rendered event is `data-testid="resource-event-<eventId>-<resourceId>"` and includes `data-resource-id`, `data-start-min`, `data-end-min`.
- **Occurrence id format in tests**: Recurring events expand via `expandRecurringEvents()`, producing ids like `rec-evt__20260514T100000` and `rec-evt__20260515T100000` (format: `<baseId>__<yyyyMMdd'T'HHmmss>`).
## 2026-02-15: Milestone 8 — GitHub Actions CI workflow

- **CI workflow structure**: Single job on `ubuntu-latest` that runs on `push` and `pull_request` to `main`/`master` branches.
- **Node setup**: Uses `actions/setup-node@v6` with `node-version: lts/*` to install Node.js.
- **Playwright installation**: Uses `npx playwright install --with-deps` to install Playwright browsers and system dependencies. The `--with-deps` flag is critical on Linux CI agents to install OS-level dependencies (fonts, libraries, etc.) required by browsers.
- **PHP setup**: Uses `shivammathur/setup-php@v2` with PHP 8.3 and required extensions. No coverage needed for CI runs.
- **Build order**: (1) `npm ci` (uses package-lock.json), (2) `npx playwright install --with-deps`, (3) `npm run build`, (4) `composer install`, (5) `composer pint:test`, (6) `composer test`.
- **Asset build before tests**: `npm run build` must run before `composer test` because `TestCase::setUp()` copies `resources/dist/*` to `public_path('vendor/livewire-calendar/')` for browser tests. If the dist files don't exist, browser tests fail to load JS/CSS.
- **Screenshot artifact upload**: Browser test screenshots (from `tests/Browser/Screenshots/`) are uploaded as artifacts with 7-day retention. Uses `if: ${{ !cancelled() }}` to upload even if tests fail.
- **Pint before tests**: `composer pint:test` runs before `composer test` to catch code style issues early (fail-fast).
- **No caching**: Browser binaries are not cached (per Playwright docs: restore time ≈ download time, and OS dependencies aren't cacheable).
- **Timeout**: 60-minute timeout prevents hung jobs from consuming CI minutes.
