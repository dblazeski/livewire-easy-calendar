# Decisions

Append-only. Record decisions with rationale.

## 2026-02-14: Remove FullCalendar deps; add Luxon + rrule

Decision: Removed `@fullcalendar/*` from `package.json` to enforce the "no FullCalendar dependency" rule and unblock Playwright installation.

Rationale: FullCalendar is a reference/spec only; Pest Browser requires Playwright which was previously blocked by the FullCalendar beta deps.

Outcome: `npm install` succeeds; Playwright runs; `npm run build` produces `resources/dist/livewire-calendar.js`.

## 2026-02-14: Update composer.json description to remove FullCalendar mention

Decision: Changed `composer.json` description from "FullCalendar-powered Livewire calendar component for Laravel." to "Livewire calendar component for Laravel with Alpine.js integration."

Rationale: The package does not depend on FullCalendar code or packages. FullCalendar is only a reference/spec for behavior. The old description was misleading to users.

Outcome: `composer test` passes; package metadata now accurately reflects the technology stack (Livewire v4 + Alpine.js).

## 2026-02-15: Add explicit calendar timeZone prop for deterministic rendering

Decision: Introduced a public Livewire prop `timeZone` (defaulting to `config('app.timezone', 'UTC')`) and emitted it to JS via `data-livewire-calendar-time-zone`.

Rationale: Recurrence expansion and DST-correct placement must be deterministic in tests and not depend on the host/browser local timezone.

Outcome: JS uses the provided IANA timezone for all parsing/formatting and for recurrence expansion across month/timeGrid/list views.
