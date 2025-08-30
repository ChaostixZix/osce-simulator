# Agents — Single-Agent Workflow

Ringkas (ID): Tidak ada lagi 3 agent terpisah. Sekarang kita gunakan 1 agent yang menangani analisis, implementasi, dan testing end-to-end. Dokumen kanban (.prompt.md, .implementation.md, .tests.md) tetap dipakai bila kita memilih alur “prompt‑first”, namun semuanya dikelola oleh satu agent.

## Why

Simplify the delivery loop. One agent owns the feature from diagnosis to implementation and testing, reducing handoffs and keeping decisions consistent.

## Default Mode — Single Agent

- One agent performs: analysis, design, implementation, tests, and docs updates.
- Use concise plans and keep the user informed with short progress updates.
- For new work, you may still generate kanban artifacts, but they are owned by the same agent.

## Prompt‑First (Optional)

When asked to blueprint first (e.g., “Blueprint this feature …” or “Make a prompt for …”), create the three files under `.claude/kanban/` with a shared kebab‑case slug. A single agent maintains all three:

- `.claude/kanban/<feature-slug>.prompt.md` — Requirements/rationale and constraints.
- `.claude/kanban/<feature-slug>.implementation.md` — What was implemented and where.
- `.claude/kanban/<feature-slug>.tests.md` — Test plan, results, and debugging notes.

Note: This preserves the structure without implying separate agents.

## General Coding (Default)

- If the user says “Let’s code: …”, proceed to implement directly without generating prompts unless requested.
- Keep changes minimal, focused, and aligned to repository conventions (see Repository Guidelines in the user instructions).
- Prefer React + Inertia for new frontend work; keep legacy Vue stable.

## Plans and Progress

- Maintain a short step‑by‑step plan using the built‑in plan tool (when useful). Keep it terse and update status as you progress.
- Share brief preambles before running tools/commands and concise progress updates during longer tasks.

## Validation

- Webapp tests: `cd webapp && composer test`.
- CLI tests: `npm test` (root).
- Build/dev: see “Build, Test, and Development Commands” in the repository guidelines.

## Security & Configuration

- Never commit secrets. Use `.env` and keep `.env.example` updated.
- For AI features, set keys in `webapp/.env` (e.g., `GEMINI_API_KEY`, `GEMINI_MODEL`).

## Migration Note (from 3‑Agent Model)

- Previous guidance split work across Diagnosis, Implementation, and Testing “agents”. We now consolidate responsibility to a single agent. The three kanban files and tasks remain compatible and are handled by the same agent.

## Triggers (Human‑Friendly)

- “Blueprint this feature: …” → Create/update the three `.claude/kanban` files; then implement.
- “Show me the prompt first” → Preview prompt without writing files.
- “Let’s code: …” → Implement directly; add tests if appropriate.
- “Run the webapp tests” / “Run the CLI tests” → Execute the respective suites and report.

## Conventions Recap

- Inertia (React): use `@inertiajs/react` (`Link`, `router`, `useForm`) for SPA interactions; no raw fetch for mutations.
- PHP: PSR‑12 and Laravel conventions.
- JS/TS: 2‑space indent, ESM, camelCase for vars/functions, PascalCase for components/classes.

