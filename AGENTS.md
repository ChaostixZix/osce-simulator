# Agents — Single Agent, Single Prompt (Implementation Only)

Ringkas (ID): Tidak ada lagi 3 agents (Diagnosis/Implementation/Testing). Hanya ADA 1 agent dan HANYA 1 dokumen prompt: implementation. Tidak ada diagnosis prompt, tidak ada testing prompt — semua fokus ke implementasi.

## Why

Simplify the delivery loop. One agent owns the feature from diagnosis to implementation and testing, reducing handoffs and keeping decisions consistent.

## Default Mode — Single Agent

- Satu agent mengerjakan: analisis singkat, implementasi, uji lokal seperlunya, dan update dokumen.
- Gunakan rencana singkat dan beri update kemajuan yang ringkas.
- Jika perlu dokumen, hanya buat satu file prompt implementasi (lihat di bawah).

Check-First Implementation (Very Important)

- Always search for existing models, routes, controllers, requests, and policies before adding new ones.
- If an endpoint or method already exists for the intended behavior, extend/reuse it instead of creating duplicates.
- Only migrate missing columns; do not add fields that already exist.
- Keep naming and route conventions consistent with what’s already in the codebase.

## Prompt Doc (Only Implementation)

Jika diperlukan dokumentasi untuk memandu pekerjaan, buat SATU file saja:

- `.claude/kanban/<feature-slug>.implementation.md` — berisi tujuan, ruang lingkup, perubahan yang akan/baru dilakukan (file dan path), langkah validasi singkat, dan catatan penting. Tidak ada `.prompt.md` atau `.tests.md` lagi.

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

- Sebelumnya ada tiga “agents” (Diagnosis/Implementation/Testing) dengan tiga dokumen. Sekarang disederhanakan: hanya satu agent dan satu dokumen prompt (`.implementation.md`). Hilangkan pembuatan `.prompt.md` dan `.tests.md`.

## Triggers (Human‑Friendly)

- “Blueprint this feature: …” → Buat/ubah satu file `.claude/kanban/<slug>.implementation.md`, lalu implement.
- “Show me the prompt first” → Preview konten implementation prompt (tanpa menulis file).
- “Let’s code: …” → Langsung implement; tambahkan uji seperlunya (tanpa membuat `.tests.md`).
- “Run the webapp tests” / “Run the CLI tests” → Jalankan test suite yang relevan dan laporkan.

## Conventions Recap

- Inertia (React): use `@inertiajs/react` (`Link`, `router`, `useForm`) for SPA interactions; no raw fetch for mutations.
- PHP: PSR‑12 and Laravel conventions.
- JS/TS: 2‑space indent, ESM, camelCase for vars/functions, PascalCase for components/classes.
