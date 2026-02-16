# Style & Conventions

## General
- Keep changes minimal and aligned with existing patterns.
- Avoid guessing: verify via repo patterns + official docs / Context7 when needed.

## PHP
- Follow existing code style and conventions.
- Prefer typed properties/arguments and explicit imports (no overly clever indirection).

## Blade
- Default package views live under `resources/views/views/`.
- Consumers can override views (and now header components) via:
  - mount props (arrays) OR
  - config keys
- Always verify custom view exists (`view()->exists(...)`) and fallback to package defaults.

## JS
- Treat JS as interactions-only (no DOM rendering).
- Preserve stable DOM selectors/attributes used by Pest Browser tests (notably `data-testid`).

## Testing
- This repo is strict TDD: add Pest + Pest Browser tests for any new behavior.
- Prefer assertions against stable `data-testid` selectors.
