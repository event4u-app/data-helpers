# Agent Task File Template

This directory contains task instructions for AI agents. Each `.md` file describes a self-contained
task or feature that an agent should implement.

---

## Rules for Writing Task Files

1. **Be precise and concise.** Aim for 500–1000 lines max. If a task exceeds that, split it into
   multiple files (e.g., `feature-part1.md`, `feature-part2.md`).
2. **Use checkboxes** (`- [ ]`) for every actionable step. Agents must check them off as they complete work.
3. **State the goal first.** One sentence at the top — what is the outcome of this task?
4. **List prerequisites** — what must exist or be running before starting.
5. **Reference existing code** — point to files, classes, or modules the agent should read first.
6. **Define acceptance criteria** — how do we know the task is done?
7. **Include quality gates** — which commands must pass before the task is considered complete.
8. **Language:** All `.md` files must be written in **English**. If an existing file is in German or
   another language, translate it to English when you touch it.
9. **Improve on read:** When an agent is asked to process a task file, it should first check whether
   the file follows this template and suggest improvements if it doesn't (e.g., missing checkboxes,
   no acceptance criteria, no quality gates). Fix the file before starting the work.
10. **Keep docs up to date:** If your changes affect something documented in an agent file (e.g.,
    `agents/testing.md`, `agents/quality-tools.md`), update that file to reflect the new state.
11. **Folder structure:** Structural documentation (architecture, systems, conventions) lives directly
    in the `agents/` folder. Roadmaps and change instructions live in `agents/roadmaps/`. If the
    current structure doesn't match, reorganize it.
12. **Suggest roadmap files:** When working on significant changes without an existing roadmap file,
    ask the user whether to create one in `agents/roadmaps/` so future agents can understand the
    context and decisions behind the changes.

---

## Quality Gates (always apply)

Every task must pass these before it is considered done:

```bash
task quality:phpstan          # Static analysis (level 9) — must pass
task quality:refactor:fix     # Auto-fix code style + refactoring
task quality:phpstan          # Re-check after Rector/ECS changes
task test:run                 # All tests must pass
```

These commands run **inside Docker containers** via Taskfile.

---

## Template

Copy the structure below into a new file, e.g., `agents/roadmaps/my-feature.md`:

```markdown
# Task: [Short descriptive title]

> [One sentence: What is the expected outcome?]

## Prerequisites

- [ ] Read `AGENTS.md` and `.github/copilot-instructions.md`
- [ ] Read [relevant module/file/class]
- [ ] Ensure Docker containers are running (`task docker:up`)

## Context

[Brief explanation of the domain context. Which part of the library does this belong to?
Which existing classes or patterns should be followed? Keep it short.]

## Steps

- [ ] **Step 1:** [Clear, actionable instruction]
- [ ] **Step 2:** [Next step — reference files/classes where helpful]
- [ ] **Step 3:** [...]
- [ ] ...

## Acceptance Criteria

- [ ] [Criterion 1 — observable, testable]
- [ ] [Criterion 2]
- [ ] All quality gates pass (PHPStan, ECS, Rector, Tests)

## Notes

[Optional: edge cases, things to watch out for, links to docs or tickets.]
```

---

## Tips for Effective Agent Instructions

- **Don't describe architecture** the agent can read from `AGENTS.md` — just reference it.
- **Don't repeat coding standards** — they live in `.github/copilot-instructions.md`.
- **Do reference specific files:** "See `src/DataMapper.php`" is better than "look at the mapper."
- **Do define boundaries:** State what the agent should NOT touch or change.
- **Do include example inputs/outputs** for non-obvious behavior.
- **Do split large tasks** — an agent works better with a focused 500-line file than a sprawling 2000-line one.
- **One task per file.** Don't combine unrelated work.

