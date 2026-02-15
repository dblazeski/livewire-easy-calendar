# Issues

Append-only. Track blockers, failures, and follow-ups.

## 2026-02-14: npm install fails due to @fullcalendar/* beta deps

- Observation: `npm install` fails with `E404 Not Found` for `@fullcalendar/locales-all@beta` and also reports an expired/revoked npm access token.
- Impact: JS build (`vite build`) cannot currently run in this repo without changing `package.json`/npm auth.
- Evidence: bash output from `npm install` during plan QA.

## 2026-02-14: npm run build fails (vite not installed)

- Observation: `npm run build` fails with `sh: vite: command not found`.
- Likely cause: `npm install` did not complete, so `node_modules` (and `vite`) are missing.
