# ExecPlans

When writing complex features or significant refactors, use an `ExecPlan` (as described in `.agent/PLANS.md`) from design to implementation.

# Docs And Context7 Are Mandatory

- Do not assume APIs, package capabilities, or integration patterns.
- Always verify with official docs and/or Context7 before implementing or changing behavior.
- If a decision cannot be verified, stop and ask for clarification rather than guessing.

# Proactive Skill Usage

- When working on Laravel or Pest tasks, proactively discover and use any relevant agent skills available in the runtime.
- If no dedicated skill exists for the topic, follow existing repo patterns and use official docs/Context7 rather than guessing.

# Testing

- This is a TDD package. Write Laravel Pest and Pest Browser tests for all features added.
