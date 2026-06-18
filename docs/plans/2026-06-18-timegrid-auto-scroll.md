# Timegrid Auto Scroll Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Port the Bukiraj `feature/ald-170-today-button` calendar auto-scroll behavior into the `dblazeski/livewire-calendar` package so time-grid views scroll the earliest visible timed event into view on first render and after Livewire navigation/morphs.

**Architecture:** Keep the behavior in the package JavaScript bundle because the package already renders stable data attributes for the calendar root, current view, initial date, timed event start minutes, and the package-specific `.lec-timegrid-body` scroll container. Do not change PHP domain/calendar date behavior; add browser regressions that exercise the public package component through Testbench and visible DOM. Rebuild the generated dist bundle after the TypeScript source change.

**Tech Stack:** PHP 8.3, Laravel 12, Livewire 4, Pest 4 Browser, TypeScript, Vite.

---

## Verified Sources

- Bukiraj source branch: `/Users/db/Code/apps/bukiraj`, branch `feature/ald-170-today-button`.
- Bukiraj branch diff against `master`: `resources/js/app.js` and `tests/Browser/Livewire/Dashboard/CalendarPageBrowserTest.php`.
- Target package: `/Users/db/Code/livewire-easy-calendar`, repo `dblazeski/livewire-easy-calendar`, branch `feature/ald-170-today-button`.
- Target package existing dirty file: `.serena/project.yml`; do not touch or stage it.
- Context7 was attempted for Livewire docs but returned `Monthly quota exceeded`. Official Livewire docs were checked instead:
  - Livewire components are PHP classes whose methods can be called from Blade.
  - `wire:click` triggers component actions.
  - Livewire 4 JavaScript docs document `livewire:init`, `livewire:initialized`, the `window.Livewire` global object, and `Livewire.hook(...)`.
  - Livewire 4 JavaScript docs document `morphed` as the hook that runs after all child elements in a component are morphed.
  - Livewire 4 Navigate docs document `livewire:navigated`; they also say `DOMContentLoaded` only fires on the first page visit under `wire:navigate`, and code that must run on every visit should use `livewire:navigated`.
- GitHub official docs were checked for `gh pr create`.

---

### Task 1: Add Browser Regression For Timegrid Auto Scroll

**Files:**
- Modify: `tests/Feature/InteractionBrowserTest.php`

**Step 1: Add test calendar fixture**

Add this fixture class below `InteractionTestCalendar`:

```php
class LateEventInteractionTestCalendar extends LivewireCalendar
{
    protected function events(DateRange $range): array
    {
        return [
            new CalendarEvent(
                id: 'late-evt-1',
                title: 'Late Visible Meeting',
                start: Carbon::parse('2026-05-14T21:00:00+00:00'),
                end: Carbon::parse('2026-05-14T21:30:00+00:00'),
            ),
        ];
    }
}
```

**Step 2: Register fixture and route**

Register the component in the existing `beforeEach()` block:

```php
Livewire::component('late-event-interaction-test-calendar', LateEventInteractionTestCalendar::class);
```

Add a package browser route in the existing `beforeEach()` block:

```php
Route::get('/test-interactions-late-event', fn () => Blade::render(<<<'HTML'
    <html>
    <head>@livewireStyles</head>
    <body>
        @livewireScripts
        <livewire:late-event-interaction-test-calendar view="timeGridWeek" initial-date="2026-05-14" first-day="0" today="2026-05-14" time-zone="UTC" />
    </body>
    </html>
HTML))->middleware('web');
```

Add a second package browser route that starts away from the event and reaches it through a real Livewire morph:

```php
Route::get('/test-interactions-late-event-today', fn () => Blade::render(<<<'HTML'
    <html>
    <head>@livewireStyles</head>
    <body>
        @livewireScripts
        <livewire:late-event-interaction-test-calendar view="timeGridWeek" initial-date="2026-05-07" first-day="0" today="2026-05-14" time-zone="UTC" />
    </body>
    </html>
HTML))->middleware('web');
```

Add a third package browser route that starts in `timeGridDay` on the event date so the review-fix regression can switch away from the timegrid and return to the same timegrid view:

```php
Route::get('/test-interactions-late-event-day', fn () => Blade::render(<<<'HTML'
    <html>
    <head>@livewireStyles</head>
    <body>
        @livewireScripts
        <livewire:late-event-interaction-test-calendar view="timeGridDay" initial-date="2026-05-14" first-day="0" today="2026-05-14" time-zone="UTC" />
    </body>
    </html>
HTML))->middleware('web');
```

**Step 3: Write failing first-render browser test**

Append this test to `tests/Feature/InteractionBrowserTest.php`:

```php
it('scrolls the timegrid body to the first timed event after render', function (): void {
    $page = visit('/test-interactions-late-event')
        ->assertPresent('[data-testid="timegrid"]')
        ->assertPresent('[data-testid="timed-event-late-evt-1-2026-05-14"]');

    $page->assertScript(<<<'JS'
        (() => {
            const body = document.querySelector('.lec-timegrid-body');
            const event = document.querySelector('[data-testid="timed-event-late-evt-1-2026-05-14"]');

            if (!body || !event) {
                return false;
            }

            const bodyRect = body.getBoundingClientRect();
            const eventRect = event.getBoundingClientRect();

            return body.scrollTop > 0
                && eventRect.top >= bodyRect.top
                && eventRect.bottom <= bodyRect.bottom;
        })()
    JS);
});
```

This event starts at 21:00. With `.lec-timegrid-body` capped at `37.5rem`, it is below the initial viewport before the fix, so the assertion must fail on the current package bundle.

**Step 4: Write failing post-morph browser test**

Append this second test to `tests/Feature/InteractionBrowserTest.php`:

```php
it('scrolls the timegrid body to the first timed event after today navigation morphs the view', function (): void {
    $page = visit('/test-interactions-late-event-today')
        ->assertPresent('[data-testid="timegrid"]')
        ->assertNotPresent('[data-testid="timed-event-late-evt-1-2026-05-14"]');

    $page->click('[data-testid="btn-today"]')
        ->assertPresent('[data-testid="timed-event-late-evt-1-2026-05-14"]');

    $page->assertScript(<<<'JS'
        (() => {
            const body = document.querySelector('.lec-timegrid-body');
            const event = document.querySelector('[data-testid="timed-event-late-evt-1-2026-05-14"]');

            if (!body || !event) {
                return false;
            }

            const bodyRect = body.getBoundingClientRect();
            const eventRect = event.getBoundingClientRect();

            return body.scrollTop > 0
                && eventRect.top >= bodyRect.top
                && eventRect.bottom <= bodyRect.bottom;
        })()
    JS);
});
```

This test exercises the package's `goToToday()` Livewire action through the real toolbar button and proves the post-morph scroll path, not just first render.

**Step 5: Write failing recreated-timegrid browser test**

Append this third test to `tests/Feature/InteractionBrowserTest.php`:

```php
it('scrolls the timegrid body after returning to the same timegrid view', function (): void {
    $page = visit('/test-interactions-late-event-day')
        ->assertPresent('[data-testid="timegrid"]')
        ->assertPresent('[data-testid="timed-event-late-evt-1-2026-05-14"]');

    $page->assertScript(<<<'JS'
        (() => {
            const body = document.querySelector('.lec-timegrid-body');
            const event = document.querySelector('[data-testid="timed-event-late-evt-1-2026-05-14"]');

            if (!body || !event) {
                return false;
            }

            const bodyRect = body.getBoundingClientRect();
            const eventRect = event.getBoundingClientRect();

            return body.scrollTop > 0
                && eventRect.top >= bodyRect.top
                && eventRect.bottom <= bodyRect.bottom;
        })()
    JS);

    $page->click('[data-testid="view-btn-resourceTimelineDay"]')
        ->assertPresent('[data-testid="resource-timeline"]')
        ->click('[data-testid="view-btn-timeGridDay"]')
        ->assertPresent('[data-testid="timed-event-late-evt-1-2026-05-14"]');

    $page->assertScript(<<<'JS'
        (() => {
            const body = document.querySelector('.lec-timegrid-body');
            const event = document.querySelector('[data-testid="timed-event-late-evt-1-2026-05-14"]');

            if (!body || !event) {
                return false;
            }

            const bodyRect = body.getBoundingClientRect();
            const eventRect = event.getBoundingClientRect();

            return body.scrollTop > 0
                && eventRect.top >= bodyRect.top
                && eventRect.bottom <= bodyRect.bottom;
        })()
    JS);
});
```

This test covers the GitHub review comment by recreating the timegrid body under a preserved Livewire root and proving the late event is visible again after the return morph.

**Step 6: Run failing tests**

Run:

```bash
composer test -- --filter "scrolls the timegrid body to the first timed event"
```

Expected before implementation: the first two tests FAIL because `.lec-timegrid-body.scrollTop` remains `0` and the late event is outside the visible scroll area. The recreated-timegrid test is expected to fail on the first implementation commit before the review fix.

---

### Task 2: Port Auto Scroll To Package TypeScript

**Files:**
- Modify: `resources/js/livewire-calendar.ts`

**Step 1: Add scroll signature state near existing root constants**

Add:

```ts
const scrollSignatures = new WeakMap<HTMLElement, { body: HTMLElement; signature: string }>();
```

**Step 2: Add focused helper functions after `getWire()`**

Add:

```ts
function getTimeGridBody(root: HTMLElement): HTMLElement | null {
    const view = root.getAttribute('data-livewire-calendar-view');

    if (view === 'timeGridWeek' || view === 'timeGridDay' || view === 'resourceTimeGridDay') {
        return root.querySelector<HTMLElement>('.lec-timegrid-body');
    }

    return null;
}

function getEarliestTimedEvent(root: HTMLElement): HTMLElement | null {
    let earliestEvent: HTMLElement | null = null;

    root.querySelectorAll<HTMLElement>('.lec-timed-event[data-start-min][data-event-id]').forEach((event) => {
        if (!earliestEvent || Number(event.dataset.startMin) < Number(earliestEvent.dataset.startMin)) {
            earliestEvent = event;
        }
    });

    return earliestEvent;
}

function getScrollSignature(root: HTMLElement, event: HTMLElement): string {
    return [
        root.getAttribute('data-livewire-calendar-view') || '',
        root.getAttribute('data-livewire-calendar-initial-date') || '',
        event.dataset.eventId || '',
        event.dataset.startMin || '',
    ].join('|');
}

function scrollTimeGrid(root: HTMLElement): void {
    const body = getTimeGridBody(root);
    if (!body) {
        scrollSignatures.delete(root);
        return;
    }

    const event = getEarliestTimedEvent(root);
    if (!event) {
        scrollSignatures.delete(root);
        return;
    }

    const signature = getScrollSignature(root, event);
    const previous = scrollSignatures.get(root);
    if (previous?.body === body && previous.signature === signature) return;

    const slot = body.querySelector<HTMLElement>('.lec-timegrid-slot');
    if (!slot) return;

    const slotHeight = slot.getBoundingClientRect().height;
    const startMinute = Number(event.dataset.startMin);

    body.scrollTop = Math.max(0, (startMinute / 30) * slotHeight - (slotHeight * 2));
    scrollSignatures.set(root, { body, signature });
}

function scheduleTimeGridScroll(root: HTMLElement): void {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => scrollTimeGrid(root));
    });
}
```

Use existing package style: simple early returns, no fallback query paths beyond package-rendered selectors, and no PHP changes. Store the body element with the signature so a preserved calendar root can still re-scroll when Livewire recreates `.lec-timegrid-body` with the same event/date signature.

**Step 3: Wire scroll refresh into initialization and Livewire lifecycle**

Update `initializeRoots()`:

```ts
function initializeRoots(): void {
    document.querySelectorAll<HTMLElement>(ROOT_SELECTOR).forEach((root) => {
        if (root.getAttribute(INITIALIZED_ATTR) !== 'true') {
            root.setAttribute(INITIALIZED_ATTR, 'true');
        }

        scheduleTimeGridScroll(root);
    });
}
```

Update lifecycle listeners:

```ts
let livewireMorphHookRegistered = false;

function registerLivewireMorphHook(): void {
    if (livewireMorphHookRegistered || !window.Livewire) {
        return;
    }

    window.Livewire.hook('morphed', ({ el }: { el: HTMLElement }) => {
        const root = el.matches(ROOT_SELECTOR)
            ? el
            : el.querySelector<HTMLElement>(ROOT_SELECTOR);

        if (root) {
            scheduleTimeGridScroll(root);
        }
    });

    livewireMorphHookRegistered = true;
}

document.addEventListener('livewire:navigated', initializeRoots);
document.addEventListener('livewire:initialized', () => {
    initializeRoots();
    registerLivewireMorphHook();
});
document.addEventListener('livewire:init', () => {
    registerLivewireMorphHook();
});
registerLivewireMorphHook();
```

If TypeScript rejects `hook` because the local `Window.Livewire` interface only declares `find()`, extend the interface with the narrow hook signature used above:

```ts
hook(name: string, callback: (payload: { el: HTMLElement }) => void): void;
```

The immediate `registerLivewireMorphHook()` call is required because this package loads `resources/dist/livewire-calendar.js` from `resources/views/livewire-calendar.blade.php` after `@livewireScripts` in the Testbench browser pages, so the asset can load after `livewire:init` has already fired. The `livewire:init` listener remains for consumers that load the package asset earlier.

**Step 4: Run test**

Run:

```bash
composer test -- --filter "scrolls the timegrid body to the first timed event"
```

Expected after implementation before rebuild: still FAIL because `TestCase::publishAssetsForBrowserTests()` publishes `resources/dist`, not `resources/js/livewire-calendar.ts`.

**Review-fix note:** GitHub review comment `discussion_r3434544255` identified that the first implementation suppressed scrolling when a timegrid was removed and later inserted again with the same earliest event signature. The final implementation ties the cache entry to both the preserved root and the concrete `.lec-timegrid-body` element, and deletes stale entries when the current view has no timegrid body or timed event.

---

### Task 3: Rebuild Dist Bundle And Verify

**Files:**
- Modify: `resources/dist/livewire-calendar.js`

**Step 1: Rebuild package assets**

Run:

```bash
npm run build
```

Expected: Vite writes `resources/dist/livewire-calendar.js` and keeps existing dist CSS.

**Step 2: Run focused browser regression**

Run:

```bash
composer test -- --filter "scrolls the timegrid body to the first timed event"
```

Expected: PASS.

**Step 3: Run adjacent package tests**

Run:

```bash
composer test -- tests/Feature/InteractionBrowserTest.php tests/Feature/TimeGridWeekTest.php tests/Feature/ResourceTimeGridDayTest.php
```

Expected: PASS.

**Step 4: Format check**

Run:

```bash
composer pint:test
```

Expected: PASS.

---

### Task 4: Commit And PR

**Files:**
- Include in commit:
  - `tests/Feature/InteractionBrowserTest.php`
  - `resources/js/livewire-calendar.ts`
  - `resources/dist/livewire-calendar.js`
  - `docs/plans/2026-06-18-timegrid-auto-scroll.md`
- Exclude:
  - `.serena/project.yml`

**Step 1: Review diff**

Run:

```bash
git status --short
git diff -- tests/Feature/InteractionBrowserTest.php resources/js/livewire-calendar.ts resources/dist/livewire-calendar.js docs/plans/2026-06-18-timegrid-auto-scroll.md
```

Expected: only the planned files changed, plus the pre-existing unstaged `.serena/project.yml`.

**Step 2: Commit atomically**

Run:

```bash
git add tests/Feature/InteractionBrowserTest.php resources/js/livewire-calendar.ts resources/dist/livewire-calendar.js docs/plans/2026-06-18-timegrid-auto-scroll.md
git commit -m "fix: scroll timegrid to visible events"
```

**Step 3: Push branch**

Run:

```bash
git push -u origin feature/ald-170-today-button
```

**Step 4: Create PR**

Run:

```bash
gh pr create --base main --head feature/ald-170-today-button --title "Scroll timegrid calendar to visible events" --body "## Summary
- port time-grid auto-scroll behavior from Bukiraj into the package bundle
- add browser regressions for first render, today navigation morphs, and returning to a recreated timegrid body
- rebuild the distributed JS asset

## Tests
- composer test -- --filter \"scrolls the timegrid body to the first timed event\"
- composer test -- tests/Feature/InteractionBrowserTest.php tests/Feature/TimeGridWeekTest.php tests/Feature/ResourceTimeGridDayTest.php
- composer pint:test"
```

**Step 5: Mandatory review after PR update**

Spawn a read-only subagent review after the PR exists. The subagent must answer `APPROVE` or `REJECT` and must not edit files, spawn agents, or delegate.
