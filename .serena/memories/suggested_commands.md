# Suggested Commands

## PHP / Laravel
- Install deps: `composer install`
- Run tests: `composer test` (runs Pest)
- Run code style check: `composer pint:test`
- Auto-fix style: `composer pint`

## Frontend / Assets
- Install node deps: `npm ci` (CI) or `npm install`
- Build package assets: `npm run build` (outputs to resources/dist)
- Install Playwright browsers (if needed): `npm run playwright:install` or `npx playwright install --with-deps`

## Docs / Workbench
- Build workbench skeleton: `composer build`
- Serve workbench: `composer serve`
- Serve docs site (port 3010): `composer docs`
  - Visit: http://127.0.0.1:3010/docs
- Rebuild docs CSS (Tailwind CLI): `npm run docs:css`

## Common Git
- `git status`
- `git diff`
- `git log --oneline -10`
