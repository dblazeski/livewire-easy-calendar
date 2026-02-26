# Fix Documentation Discrepancies Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all discrepancies between documentation (README, workbench docs, composer.json, AGENTS.md) and the actual codebase.

**Architecture:** Text-only changes across markdown, blade templates, JSON, and PHP config. No code logic changes. Each task targets one discrepancy and the files that contain it.

**Tech Stack:** Blade templates, Markdown, JSON (composer.json), PHP

---

## Discrepancy Summary

| # | Issue | Severity |
|---|-------|----------|
| 1 | GitHub URLs point to wrong repo name | High |
| 2 | Alpine.js falsely claimed; codebase uses vanilla JS | High |
| 3 | `resourceTimeGridDay` view completely undocumented | Medium |
| 4 | Mount params `views`, `components`, `eventTimeManagementEnabled` undocumented | Medium |
| 5 | `color`/`backgroundColor` event field undocumented | Medium |
| 6 | Recurrence docs say "browser expansion" but it's server-side PHP | Medium |
| 7 | Resource timeline docs say "calls fetchResources/fetchEvents" but render path is direct | Low |
| 8 | Configuration docs omit config-file features (views, components, styles) | Medium |
| 9 | AGENTS.md references Alpine.js | Low |
| 10 | DateRange docs claim data comes from browser `fetchEvents` call — stale for Blade-first render path | Medium |
| 11 | Docs index feature list doesn't mention resource time grid view | Low |

---

### Task 1: Fix GitHub URLs (repo name: livewire-calendar → livewire-easy-calendar)

**Files:**
- Modify: `workbench/resources/views/docs/_nav.blade.php:93`
- Modify: `workbench/resources/views/docs/index.blade.php:29`

**Step 1: Fix nav GitHub link**

In `workbench/resources/views/docs/_nav.blade.php` line 93, change:
```
href="https://github.com/dblazeski/livewire-calendar"
```
to:
```
href="https://github.com/dblazeski/livewire-easy-calendar"
```

**Step 2: Fix index page GitHub link**

In `workbench/resources/views/docs/index.blade.php` line 29, change:
```
href="https://github.com/dblazeski/livewire-calendar"
```
to:
```
href="https://github.com/dblazeski/livewire-easy-calendar"
```

**Step 3: Commit**

```bash
git add workbench/resources/views/docs/_nav.blade.php workbench/resources/views/docs/index.blade.php
git commit -m "fix: correct GitHub URLs to livewire-easy-calendar"
```

---

### Task 2: Remove false Alpine.js claims

The codebase uses vanilla JS + luxon. Alpine.js is NOT used by the calendar component (only by the docs layout shell for mobile nav toggle, which is unrelated to the package itself).

**Files:**
- Modify: `README.md:3` (description line)
- Modify: `README.md:237` (architecture section)
- Modify: `composer.json:3` (description field)
- Modify: `workbench/resources/views/docs/index.blade.php:14`
- Modify: `AGENTS.md:13`

**Step 1: Fix README description (line 3)**

Change:
```
A Livewire calendar component for Laravel with Alpine.js integration. Provides multiple calendar views...
```
to:
```
A Livewire calendar component for Laravel. Provides multiple calendar views...
```

**Step 2: Fix README architecture section (line 237)**

Change:
```
All rendering and interaction logic is implemented from scratch using Livewire, Alpine.js, and vanilla JavaScript.
```
to:
```
All rendering and interaction logic is implemented from scratch using Livewire and vanilla JavaScript.
```

**Step 3: Fix composer.json description**

Change:
```json
"description": "Livewire calendar component for Laravel with Alpine.js integration.",
```
to:
```json
"description": "Livewire calendar component for Laravel.",
```

**Step 4: Fix docs index tagline (line 14)**

Change:
```
Built with Livewire and Alpine.js; no third-party calendar UI libraries.
```
to:
```
Built with Livewire; no third-party calendar UI libraries.
```

**Step 5: Fix AGENTS.md (line 13)**

Change:
```
- When working on Laravel, Pest, or Alpine.js tasks, proactively discover and use any relevant agent skills available in the runtime.
```
to:
```
- When working on Laravel or Pest tasks, proactively discover and use any relevant agent skills available in the runtime.
```

**Step 6: Commit**

```bash
git add README.md composer.json workbench/resources/views/docs/index.blade.php AGENTS.md
git commit -m "fix: remove false Alpine.js claims from docs, package uses vanilla JS"
```

---

### Task 3: Add `resourceTimeGridDay` to README and docs index

**Files:**
- Modify: `README.md:117` (supported views list)
- Modify: `README.md:3` (description — add "resource time grid" to the view list mention)
- Modify: `workbench/resources/views/docs/index.blade.php:44` (features array, update view count/list)

**Step 1: Add resourceTimeGridDay to README views list**

After the line:
```
- `resourceTimelineDay`: Single-day timeline with resource rows
```
add:
```
- `resourceTimeGridDay`: Single-day time grid with one column per resource
```

**Step 2: Update docs index feature list**

In `workbench/resources/views/docs/index.blade.php` line 44, the feature `01` says:
```
'desc' => 'Month, week, day, list, year, and resource timeline.'
```
Change to:
```
'desc' => 'Month, week, day, list, year, resource timeline, and resource time grid.'
```

**Step 3: Commit**

```bash
git add README.md workbench/resources/views/docs/index.blade.php
git commit -m "docs: add resourceTimeGridDay to supported views list and feature summary"
```

---

### Task 4: Add `resourceTimeGridDay` to docs nav

**Files:**
- Modify: `workbench/resources/views/docs/_nav.blade.php:20` (Views section)

**Step 1: Add nav entry**

In the Views section array, after the `Resource Timeline` entry, add:
```php
['label' => 'Resource Time Grid', 'href' => '/docs/views/resource-timegrid-day'],
```

**Step 2: Commit**

```bash
git add workbench/resources/views/docs/_nav.blade.php
git commit -m "docs: add resource time grid day to nav"
```

---

### Task 5: Create `resourceTimeGridDay` docs page

**Files:**
- Create: `workbench/resources/views/docs/views/resource-timegrid-day.blade.php`
- Reference: `resources/views/views/resource-timegrid-day.blade.php` (actual blade view for selector names)
- Reference: `tests/Feature/ResourceTimeGridDayTest.php` (for verified selectors)

**Step 1: Read the actual blade view for data-testid selectors**

Read `resources/views/views/resource-timegrid-day.blade.php` and `tests/Feature/ResourceTimeGridDayTest.php` to extract the exact stable selectors.

**Step 2: Create the docs page**

Create `workbench/resources/views/docs/views/resource-timegrid-day.blade.php` following the same structure as `resource-timeline.blade.php`:

```blade
@extends('layouts.docs')

@section('title', 'Resource Time Grid (Day)')

@section('content')
    <div class="docs-prose">
        <h1>Resource Time Grid (Day)</h1>

        <p>
            Use <code>resourceTimeGridDay</code> to render a single-day vertical time grid with one column per resource.
        </p>

        <pre><code>&lt;livewire:my-calendar view=&quot;resourceTimeGridDay&quot; initial-date=&quot;2026-05-14&quot; /&gt;</code></pre>

        <h2>Stable Selectors</h2>

        <ul>
            <!-- Populate from actual blade view data-testid attributes -->
        </ul>
    </div>
@endsection
```

Exact selectors to be confirmed from reading the blade view at step 1.

**Step 3: Verify the route handles the new page**

The route `workbench/routes/web.php` uses a regex guard for `/docs/{page}`. Verify `views/resource-timegrid-day` matches.

**Step 4: Commit**

```bash
git add workbench/resources/views/docs/views/resource-timegrid-day.blade.php
git commit -m "docs: add resourceTimeGridDay view documentation page"
```

---

### Task 6: Document missing mount parameters in docs

**Files:**
- Modify: `workbench/resources/views/docs/api/props.blade.php:16-22` (mount signature)
- Modify: `workbench/resources/views/docs/configuration.blade.php:14-55` (props table)
- Modify: `README.md:123-134` (Component Props section)

**Step 1: Update props.blade.php mount signature**

Change the mount signature from:
```
public function mount(
    ?string $initialDate = null,
    int $firstDay = 0,
    ?string $today = null,
    ?string $view = null,
    ?string $timeZone = null,
): void
```
to:
```
public function mount(
    ?string $initialDate = null,
    int $firstDay = 0,
    ?string $today = null,
    ?string $view = null,
    ?string $timeZone = null,
    ?array $views = null,
    ?array $components = null,
    ?bool $eventTimeManagementEnabled = null,
): void
```

Also add defaults for the new params to the Defaults list:
- `views` defaults to `[]`
- `components` defaults to `[]`
- `eventTimeManagementEnabled` defaults to `true`

**Step 2: Update configuration.blade.php props table**

Add three new rows to the table after the `time-zone` row:

| Prop | Type | Default | Notes |
|------|------|---------|-------|
| `:views` | `array` | `[]` | Per-view blade path overrides |
| `:components` | `array` | `[]` | Per-component blade path overrides |
| `:event-time-management-enabled` | `bool` | `true` | Set to `false` to disable drag-and-drop and resize |

Note: Array/bool props use the `:` prefix in Blade (Livewire binding syntax).

Also add a concrete usage example after the table:

```blade
<livewire:my-calendar
    :views="['month' => 'my-views.custom-month']"
    :components="['header' => 'my-views.custom-header']"
    :event-time-management-enabled="false"
/>
```

**Step 3: Update README mount example**

Add the three new parameters to the mount example code block.

**Step 4: Commit**

```bash
git add workbench/resources/views/docs/api/props.blade.php workbench/resources/views/docs/configuration.blade.php README.md
git commit -m "docs: document views, components, and eventTimeManagementEnabled mount params"
```

---

### Task 7: Document `color`/`backgroundColor` event field

**Files:**
- Modify: `README.md:67-73` (Optional fields list)

**Step 1: Add color to optional event fields**

After the `resourceId` line in the optional fields list, add:
```
- `color` (string): Hex color for per-event background override (e.g., `#ef4444`). Accepts `#RGB`, `#RRGGBB`, or `#RRGGBBAA`.
- `backgroundColor` (string): Same as `color` but takes precedence when both are present.
```

Note: The actual rendering logic is `$event['backgroundColor'] ?? ($event['color'] ?? '')` — so `backgroundColor` wins if both are set.

**Step 2: Commit**

```bash
git add README.md
git commit -m "docs: document color/backgroundColor event fields"
```

---

### Task 8: Fix recurrence docs — expansion is server-side PHP, not browser

**Files:**
- Modify: `workbench/resources/views/docs/recurrence/rrule.blade.php:11`

**Step 1: Fix the claim**

Change:
```
The package expands occurrences in the browser using the <code>rrule</code> library.
```
to:
```
The package expands occurrences on the server using <code>rlanvin/php-rrule</code>.
```

**Step 2: Commit**

```bash
git add workbench/resources/views/docs/recurrence/rrule.blade.php
git commit -m "fix: correct recurrence docs — expansion is server-side PHP"
```

---

### Task 9: Fix resource timeline docs — render path description

**Files:**
- Modify: `workbench/resources/views/docs/views/resource-timeline.blade.php:11`

**Step 1: Fix the claim**

Change:
```
This view calls both <code>fetchResources</code> and <code>fetchEvents</code>.
```
to:
```
This view uses your <code>resources()</code> and <code>events()</code> methods to build the timeline.
```

**Step 2: Commit**

```bash
git add workbench/resources/views/docs/views/resource-timeline.blade.php
git commit -m "fix: correct resource timeline render path description in docs"
```

---

### Task 10: Add config-file documentation to configuration page

**Files:**
- Modify: `workbench/resources/views/docs/configuration.blade.php`

**Step 1: Add config file section**

After the existing props table section, add a new section documenting the publishable config file:

```blade
<h2>Config File</h2>

<p>
    Publish the config file to customise global defaults:
</p>

<pre><code>php artisan vendor:publish --tag=livewire-calendar</code></pre>

<p>
    The config file (<code>config/livewire-calendar.php</code>) supports:
</p>

<ul>
    <li><code>views</code> — global view overrides (same as the mount parameter, applied to all instances)</li>
    <li><code>components</code> — global component overrides (header, nav buttons, view switchers)</li>
    <li><code>styles</code> — CSS custom property values for theming (font, event colors, selection, drag ghost, etc.)</li>
</ul>

<p>
    Mount parameters take precedence over config-file values. Config values take precedence over built-in defaults.
</p>
```

**Step 2: Commit**

```bash
git add workbench/resources/views/docs/configuration.blade.php
git commit -m "docs: document config file (views, components, styles)"
```

---

### Task 11: Fix DateRange docs — stale fetchEvents browser-flow claim

**Files:**
- Modify: `workbench/resources/views/docs/api/date-range.blade.php:23-27`

**Step 1: Fix the "Where it comes from" section**

Change:
```html
<p>
    In the browser, the calendar requests data by calling Livewire methods like <code>fetchEvents</code>.
    Those methods receive <code>start</code> and <code>end</code> strings (often <code>YYYY-MM-DD</code>), which are converted
    into a <code>DateRange</code>.
</p>
```
to:
```html
<p>
    During rendering, the component computes the visible date range and passes it as a
    <code>DateRange</code> to your <code>events()</code> and <code>resources()</code> hooks.
</p>
```

**Step 2: Commit**

```bash
git add workbench/resources/views/docs/api/date-range.blade.php
git commit -m "fix: correct DateRange docs — render path uses events()/resources() hooks"
```

---

### Task 12: Final verification sweep

After all tasks are complete, run a grep sweep to confirm no stale references remain.

**Step 1: Scan for residual stale strings**

```bash
grep -rn "Alpine\.js" README.md composer.json AGENTS.md workbench/resources/views/docs/
grep -rn "fetchResources\|fetchEvents" workbench/resources/views/docs/
grep -rn "github.com/dblazeski/livewire-calendar[^-]" workbench/resources/views/docs/
grep -rn "in the browser" workbench/resources/views/docs/recurrence/
```

**Step 2: Fix any remaining hits**

If any stale references are found, fix them in place.

**Step 3: Commit if changes were needed**

```bash
git add -A
git commit -m "chore: clean up residual stale doc references"
```

---

## Execution Notes

- Tasks 1-2 are the highest priority (wrong URLs and false tech claims)
- Tasks 3-5 go together (resourceTimeGridDay documentation)
- Task 5 requires reading the actual blade view to extract correct data-testid selectors
- All changes are text/docs only — no code logic changes
- The composer package name (`dblazeski/livewire-calendar`) vs repo name (`livewire-easy-calendar`) mismatch is noted but NOT changed here — changing the package name would be a breaking change for anyone who has already installed it. This should be a separate decision by the maintainer.
