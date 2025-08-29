# Diagnosis: Implement Full OSCE Clinical Workflow (React + Inertia) — AI patient chat, physical exam, test ordering, rationalization, results/assessment

Feature slug: osce-clinical-workflow

## Purpose

Deliver feature parity with the legacy Vue OSCE experience in the new React + Inertia SPA. Users (medical trainees) must be able to: start an OSCE session, chat with an AI patient, conduct physical exams, order tests with reasoning/priority, complete a structured rationalization review (cards, diagnosis, care plan), and then unlock and view assessment results. This aligns the app with the pedagogy of OSCE: history-taking → exam → investigations → clinical reasoning → management → reflection → assessment.

## Scope

- Build React pages/components to replace legacy Vue views for: chat console, rationalization, and results detail.
- Wire missing server routes for Rationalization flows to existing controllers/services.
- Respect gating rules: results are unlocked only after rationalization completion.
- Ensure Inertia navigation and JSON APIs are used per conventions.

Inputs/Outputs
- Inputs: User chat messages, selected exam categories/types, ordered medical tests (id + reasoning + priority), rationalization card rationales, primary/differential diagnoses, structured care plan text.
- Outputs: Persisted chat transcript, exam findings, ordered tests + generated results, rationalization entries, assessment runs and scores; JSON responses for polling/status.

Return types & side effects
- Server returns JSON for API endpoints; Inertia page props for page loads. Side effects: DB writes (messages, exams, orders, rationalization entries), queue jobs for assessment/evaluation.

## App Context (What this app is)

An OSCE training platform where learners interact with AI-simulated patients to practice clinical skills. The app supports case selection, timed sessions, realistic conversations with an AI patient (Gemini), physical exams, ordering and interpreting tests, reflective rationalization to justify clinical decisions, and automated assessment with detailed feedback. Stack: Laravel backend, Inertia SPA (React in active migration), Vite, Tailwind; SQLite in dev. Timezone Asia/Jakarta; 24h formatting; no secrets in repo.

## Code References (existing)
- Routes: `webapp/routes/web.php`
- Controllers: `webapp/app/Http/Controllers/OsceController.php`, `OsceChatController.php`, `OsceAssessmentController.php`, `RationalizationController.php`, `OsceRationalizationController.php` (minimal Inertia wrapper)
- Models: `webapp/app/Models/OsceSession*.php`, `AnamnesisRationalizationCard.php`, `OsceDiagnosisEntry.php`, `SessionExamination.php`, `SessionOrderedTest.php`, `AiAssessmentRun.php`
- Services: `webapp/app/Services/AiPatientService.php`, `GeminiService.php`, `RationalizationService.php`
- React pages: `webapp/resources/js/pages/Osce.jsx`, `OsceChat.jsx`, `OsceRationalization.jsx` (placeholder), `OsceResult.jsx` (minimal), `OsceResults.jsx` (legacy)
- Legacy Vue (reference UIs): `webapp/resources/js/Pages/Rationalization/Show.vue`, `Components/Rationalization/*.vue`, `components/osce/*.vue`

## Constraints
- Inertia rules (React): Use `@inertiajs/react` router for navigations/mutations; prefer JSON endpoints only for pure data fetch; no manual CSRF when using Inertia.
- Keep Vue and React isolated by page; do not mix adapters.
- SQL LIKE search only (no external search engines) for any lists.
- Storage/local DB in dev; long-running work via queue.

## Error Handling
- Chat: when session inactive/expired, return 400 with `time_status`; UI disables inputs.
- Orders/Exams: gracefully skip duplicates; show inline errors; reflect `no_data` results for unavailable tests.
- Rationalization: server validation for min lengths; return structured JSON errors.
- Assessment: status endpoint returns clear phases; results gated with 403 until rationalization done.

## Objective Actions (do exactly this)

1) Backend routes (Rationalization)
- Add to `webapp/routes/web.php` under `auth` group:
  - `POST /rationalization/cards/{card}/answer` → `RationalizationController@answerCard` → name(`rationalization.answer-card`)
  - `POST /rationalization/{rationalization}/diagnoses` → `RationalizationController@submitDiagnoses` → name(`rationalization.submit-diagnoses`)
  - `POST /rationalization/{rationalization}/care-plan` → `RationalizationController@submitCarePlan` → name(`rationalization.submit-care-plan`)
  - `GET /rationalization/{rationalization}/progress` → `RationalizationController@progress` → name(`rationalization.progress`)
  - `POST /rationalization/{rationalization}/complete` → `RationalizationController@complete` → name(`rationalization.complete`)

2) Backend APIs (JSON variants)
- Add `POST /api/osce/examinations` → mirror `performExamination` but return JSON with updated findings and ordered exams; keep existing redirect route for legacy.
- Optional: add `POST /api/osce/procedures` → mirror `orderProcedure` with JSON.

3) React — Chat console (extend `pages/OsceChat.jsx`)
- Add left sidebar with Case Overview (scenario/objectives/vitals) and session widgets (timer from `/api/osce/sessions/{id}/timer`, total test cost, exam results list).
- Add “Order Tests” modal:
  - Search `/api/medical-tests/search?q=...` (>=2 chars); list with `name`, `category`, `type`, `cost`.
  - Selection requires `clinicalReasoning` (>=20 chars) and `priority` (immediate|urgent|routine).
  - Submit via `POST /api/osce/order-tests` with `{ session_id, orders: [{ medical_test_id, clinical_reasoning, priority }] }`; handle `no_data` results.
- Add Physical Exam selector using provided `examCatalog` prop; submit to new `POST /api/osce/examinations`; update results list.
- Respect session active state: disable inputs when inactive; auto-complete if expired.

4) React — Rationalization page (new `pages/Rationalization.jsx`)
- Fetch props via `RationalizationController@show` (use `Inertia::render('Rationalization/Show', ...)` shape for parity) or pass needed props from `OsceRationalizationController` to match required data.
- Sections:
  - Cards: group by type (asked_question, negative_anamnesis, investigation); card UI mirrors `RationalizationCard.vue`; submit answers to `rationalization.answer-card` (include `marked_as_forgot`).
  - Diagnosis: primary + multiple differentials, validate 50/30 chars; draft to `localStorage` every 10s; submit to `rationalization.submit-diagnoses`.
  - Care Plan: rich text or textarea MVP; min 100 chars; autosave every 10s and on blur; submit to `rationalization.submit-care-plan`; show Saved/Saving indicator.
  - Progress: poll `rationalization.progress` every 30s; enable “Complete & Unlock Results” if `can_unlock`; POST `rationalization.complete` then navigate to `osce.results.show`.

5) React — Results page (extend `pages/OsceResult.jsx`)
- Render performance overview (overall % and band, clinical reasoning score %, total test cost; `Reassess` button → `osce.assess.trigger`).
- Show assessment data from `AiAssessmentRun` when available: clinical areas with score badges, justifications, citations; fallback to legacy session fields when no run.
- Add polling action to `GET osce.status` for progress feedback while assessing.

6) UX/Rules
- Timezone locale Asia/Jakarta; 24h, DD/MM/YYYY where applicable; relative badges “x minutes ago”.
- Disable actions during `isLoading`/pending; guard double submits; debounce search.
- No file uploads in this scope; attachments out-of-scope for this module.

7) Validation
- Enforce backend rules already coded:
  - Diagnoses: primary_reasoning >=50, each differential reasoning >=30.
  - Care plan: >=100 characters (HTML stripped length).
  - Test order: reasoning >=20; `priority` required; prevent duplicates.

8) Acceptance Criteria
- OSCE chat allows sending/receiving; session inactive blocks inputs and can auto-complete.
- Physical exams can be selected and persist with timestamps; duplicates ignored.
- Test ordering: search, select, validate, submit; total cost and turnaround shown; unavailable tests yield `no_data` results.
- Rationalization page shows cards, diagnosis form, and care plan editor; auto-draft and autosave work; Completing unlocks results.
- Results page shows assessment summary; reassess triggers orchestrator and progress can be polled; legacy data still render if no new run.
- Inertia navigations used for page transitions; JSON used for polling/data-only flows.

## Out of Scope
- Moving to a single adapter that mixes Vue and React — pages remain React-only.
- Notifications, export/print, audit logs, or advanced analytics dashboards.
- Full-text search engines (Meilisearch/Typesense) — stick to SQL LIKE.
- File attachments and comments (not needed in this module).

## Commands
- Webapp setup: `cd webapp && composer install && npm install`
- Dev: `composer dev` (serves Laravel, queue, logs, Vite)
- Migrate/seed: `php artisan migrate --seed`
- Tests: `composer test`

## Notes
- Rationalization backend is already implemented (`RationalizationService`, models); only routes and React UI are missing.
- Legacy Vue components (`resources/js/Pages/Rationalization/Show.vue`) are reference-only; they don’t mount under the React Inertia app.

