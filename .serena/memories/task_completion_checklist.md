# Task Completion Checklist

When finishing a change:
- Run PHP style check: `composer pint:test`
- Run tests: `composer test`
- If JS/CSS changed, rebuild dist assets: `npm run build`
- If docs CSS changed (workbench Tailwind source), run: `npm run docs:css`
- Ensure docs site still runs: `composer docs` (port 3010)
