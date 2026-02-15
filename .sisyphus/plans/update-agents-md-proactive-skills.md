# Update AGENTS.md: Proactive Skills For Laravel/Pest/Alpine.js

## TL;DR

Add a small, directive section to `AGENTS.md` telling future agents to proactively use relevant agent skills when working on Laravel, Pest, or Alpine.js. Keep existing content unchanged and avoid enumerating specific skill names.

**Estimated Effort**: Quick
**Parallel Execution**: NO (single-file change)
**Critical Path**: Task 1

---

## Context

### Original Request
"update local AGENTS.md ... ask it to use them proactivelly" (clarified: "just say please proactivelly use agents skills for laravel and pest and alpinejs")

**Additional Requirement**: "This will be TDD package, with Laravel Pest and Pest Browser tests for all features added" - must be stored in `AGENTS.md`.

### Repo Evidence
- `AGENTS.md` is currently only 10 lines with two sections: ExecPlans + Docs/Context7 notes.

### Guardrails (Metis)
- Do not restructure or rewrite existing `AGENTS.md` sections; append only.
- Keep it generic: do not list skill names or slash-commands.
- Mention exactly: Laravel, Pest, Alpine.js (no extra tech scope).
- Keep the addition short (aim: <= 8 new lines).

---

## Work Objectives

### Core Objective
Ensure repo-level agent guidance explicitly instructs proactive skill usage for Laravel, Pest, and Alpine.js work.

### Definition of Done
- `AGENTS.md` contains a new section referencing Laravel, Pest, and Alpine.js.
- `AGENTS.md` includes TDD/testing guidance: Pest + Pest Browser tests for all features.
- Existing content remains intact.
- No specific skill names or slash-commands are enumerated.

---

## Verification Strategy

This change is verified via agent-run commands only (no human review required).

---

## TODOs

- [x] 1. Append a concise proactive-skills section to `AGENTS.md`

  **What to do**:
  - Open `AGENTS.md` and append a new top-level section at the end.
  - Use the same tone/style as the existing file (short heading + bullets).
  - Include wording that explicitly says to proactively use relevant agent skills for:
    - Laravel
    - Pest
    - Alpine.js
  - Add a separate "Testing" section stating this is a TDD package requiring Pest + Pest Browser tests for all features.
  - Keep it generic (do not name specific skills; do not include slash-command names).

  **Proposed text to append** (verbatim):
  
  ```markdown
  # Proactive Skill Usage
  
  - When working on Laravel, Pest, or Alpine.js tasks, proactively discover and use any relevant agent skills available in the runtime.
  - If no dedicated skill exists for the topic, follow existing repo patterns and use official docs/Context7 rather than guessing.
  
  # Testing
  
  - This is a TDD package. Write Laravel Pest and Pest Browser tests for all features added.
  ```

  **Must NOT do**:
  - Do not change existing sections in `AGENTS.md`.
  - Do not add instructions for technologies not requested (e.g., Livewire, Tailwind, etc.).
  - Do not enumerate or hardcode skill names (no `laravel-skill`, no `/something`).

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: none required (single markdown edit)

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocked By**: None
  - **Blocks**: None

  **References**:
  - `AGENTS.md` - The file to update (append-only; preserve current style).
  - `.sisyphus/drafts/agents-laravel-skills.md` - Planning notes and guardrails from interview.

  **Acceptance Criteria (agent-executable)**:
  - `AGENTS.md` still contains "ExecPlans" and "Context7" references (existing guidance preserved).
  - `AGENTS.md` contains all three strings: "Laravel", "Pest", and "Alpine".
  - `AGENTS.md` does NOT contain any of the following (case-insensitive): `laravel-skill`, `filament-expert`, `/laravel`, `/pest`, `/alpine`.
   - `wc -l AGENTS.md` is <= 30 (prevents bloat; adjusted for TDD section).

  **Agent-Executed QA Scenario**:
  
  ```
  Scenario: Verify AGENTS.md guidance updated without bloat
    Tool: Bash
    Preconditions: Repo working tree available
    Steps:
      1. grep -c "ExecPlans" AGENTS.md
      2. grep -c "Context7" AGENTS.md
      3. grep -c "Laravel" AGENTS.md
      4. grep -c "Pest" AGENTS.md
      5. grep -c "Alpine" AGENTS.md
      6. grep -ci "laravel-skill\|filament-expert\|/laravel\|/pest\|/alpine" AGENTS.md
       7. grep -c "TDD\|Pest Browser" AGENTS.md
       8. wc -l AGENTS.md
    Expected Result:
      - Steps 1-5 each return >= 1
      - Step 6 returns 0
      - Step 7 returns >= 1
      - Step 8 returns <= 30
    Evidence:
      - Save combined outputs to .sisyphus/evidence/agents-md-proactive-skills-check.txt
  ```

---

## Success Criteria

- Repo-level agent instructions in `AGENTS.md` now include proactive-skill guidance for Laravel/Pest/Alpine.js, without expanding scope or altering existing guidance.
