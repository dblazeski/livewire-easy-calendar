# Decisions

Append-only. Record decisions with rationale.

## 2026-02-14: Plan Updated to Include TDD/Testing Requirement

**Decision**: Updated `.sisyphus/plans/update-agents-md-proactive-skills.md` to include the additional user requirement: "This will be TDD package, with Laravel Pest and Pest Browser tests for all features added."

**Changes Made**:
- Added TDD requirement to "Original Request" context section
- Updated "Definition of Done" to include TDD/testing guidance
- Modified proposed AGENTS.md text to add a "Testing" section with TDD + Pest + Pest Browser directive
- Updated acceptance criteria: increased line count limit from 25 to 30, added grep check for "TDD\|Pest Browser"
- Updated task instructions to mention the new Testing section

**Rationale**: User confirmed this is a core requirement that must be documented in `AGENTS.md` alongside the proactive skill usage guidance. Kept addition minimal (3 lines) to preserve the "short and focused" guardrail.

## 2026-02-14: Plan Task 1 Marked Complete

**Decision**: Marked TODO checkbox for task 1 ("Append a concise proactive-skills section to `AGENTS.md`") as completed in `.sisyphus/plans/update-agents-md-proactive-skills.md`.

**Rationale**: AGENTS.md was successfully updated with both the proactive skills section and TDD testing guidance. Evidence file at `.sisyphus/evidence/agents-md-proactive-skills-check.txt` confirms all acceptance criteria passed.
