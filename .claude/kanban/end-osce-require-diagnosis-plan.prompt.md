Feature slug: end-osce-require-diagnosis-plan

Purpose

- Add an explicit “End OSCE” flow that forces the user to fill out a required summary before finalizing an OSCE session. The modal must collect three required fields: Diagnosis (Diagnosa), Differential Diagnosis (Diagnosis Banding), and Plan (Rencana). This ensures clinical reasoning is captured consistently at the end of every OSCE session and prevents empty or incomplete finalizations.

Scope

- Add a modal triggered by the user’s End OSCE action.
- Require and validate three fields: `diagnosis`, `differentialDiagnosis`, `plan`.
- Submit via Inertia form to finalize an active OSCE session; persist the fields into the session’s final record.
- Block finalization until all three fields are present and valid; display validation errors inline in the modal.
- After successful finalization, lock further edits for non‑admins; admins can override for edits if policy allows.

Inputs/Outputs

- Inputs (frontend modal): `diagnosis` string (required), `differentialDiagnosis` string (required), `plan` string (required).
- Output (backend): Updates the OSCE session record, sets `state=finalized`, persists the three fields, sets `finalized_at=now()`; returns Inertia redirect to the session summary page.
- Side effects: Prevents further modification by non‑admin users; emits standard flash message on success; returns validation errors on failure.

Code References (expected / to create if missing)

- Routes: `webapp/routes/web.php` and/or `webapp/routes/api.php` (if JSON endpoint is separate). Prefer Inertia POST/PUT via `web.php` when the end action is part of SPA flow.
- Controllers: `webapp/app/Http/Controllers/OSCE/SessionController.php` (or `OsceSessionController.php`) — add `finalize()` method that accepts the required fields; or create a dedicated `SessionFinalizeController` if preferred.
- Request validation: `webapp/app/Http/Requests/OSCE/FinalizeSessionRequest.php` (FormRequest) for strict validation.
- Models: `webapp/app/Models/OsceSession.php` — ensure it has fields: `diagnosis`, `differential_diagnosis`, `plan`, `state`, `finalized_at`.
- Migration (if fields missing): add columns to `osce_sessions` table: `diagnosis` (text), `differential_diagnosis` (text), `plan` (text), `state` enum[`draft`,`finalized`] (default `draft`), `finalized_at` nullable timestamp.
- Policies: `webapp/app/Policies/OsceSessionPolicy.php` — `finalize`, `update` rules (admin override allowed).
- Frontend trigger and modal: React (preferred for new work) or Vue (if page is still legacy). Place under `webapp/resources/js/Pages/OSCE/`:
  - React: `EndOsceModal.jsx` (or `.tsx`), integrate in the session page component; use `@inertiajs/react` `useForm` to submit.
  - Vue: `EndOsceModal.vue` if the page is Vue. Do not mix adapters on the same page.

Constraints & Conventions

- Inertia interactions only: use `useForm` or `router.post/put` from `@inertiajs/react` (or Vue equivalent) — no raw fetch for form submit.
- CSRF is handled by Inertia — do not add headers manually.
- Validation: All three fields are required strings; trim client‑side; server enforces via FormRequest.
- UX: Modal must not close on validation error; show field‑level errors. Disable submit while processing.
- Authorization: Only the session owner (or permitted roles) can finalize; admins may override finalized records for edits if the policy allows.
- Timezone: Asia/Jakarta; 24h display; dates `DD/MM/YYYY` when shown.

Error Handling

- Missing fields: return 422 with per‑field errors; keep modal open; show errors inline.
- Unauthorized: return 403 and show a toast/flash message; modal closes.
- Conflict: if session already finalized, return 409 with friendly message and do not mutate data.
- Server error: return 500, flash generic error, modal remains closable; no partial saves.

Acceptance Criteria

1) Clicking End OSCE opens a modal with three fields: `Diagnosis`, `Differential Diagnosis`, and `Plan` — all required.
2) Submitting with any empty field shows inline validation errors and keeps the modal open.
3) Valid submit finalizes the session, persists the three fields, sets `finalized_at`, locks further edits for non‑admin users, and redirects to the session summary/overview.
4) Admin users can still edit a finalized session if the policy allows (override path); non‑admins cannot.
5) Attempting to finalize an already‑finalized session shows a clear error (no duplicate action).
6) Inertia form shows processing state; submit button is disabled while saving.
7) All strings and times render appropriately for the Asia/Jakarta timezone; 24h format where applicable.

Out of Scope

- Autosave, drafts of the modal content, or multi‑step wizards.
- PDF export, notifications, or external integrations.
- Analytics/metrics beyond existing logging.

Implementation Details (do exactly this)

- Backend
  - Migration (if needed):
    - Table: `osce_sessions` — add columns `diagnosis` text, `differential_diagnosis` text, `plan` text; ensure `state` enum[`draft`,`finalized`] (default `draft`), `finalized_at` timestamp nullable.
  - Policy: `finalize(User $u, OsceSession $s)` returns admin OR session owner and `state==='draft'`.
  - Route (web):
    - `POST /api/osce/sessions/{session}/finalize` → `OsceSessionController@finalize` (name: `osce.sessions.finalize`).
  - Validation (FormRequest):
    - `diagnosis` => `required|string`;
    - `differential_diagnosis` => `required|string`;
    - `plan` => `required|string`.
  - Controller `finalize()`:
    - Authorize `finalize`.
    - If already finalized → 409.
    - Validate; fill fields; set `state='finalized'`, `finalized_at=now()`; save.
    - Return Inertia redirect back to the session page with flash `success`.

- Frontend (React + Inertia preferred)
  - In the OSCE Session page (e.g., `resources/js/Pages/OSCE/SessionPage.jsx`), add an “End OSCE” button that opens `EndOsceModal`.
  - `EndOsceModal.jsx`:
    - Local state via `useForm({ diagnosis: '', differential_diagnosis: '', plan: '' })`.
    - Required indicators, character‑friendly textareas; show `errors.diagnosis`, `errors.differential_diagnosis`, `errors.plan`.
    - On submit: `form.post(route('osce.sessions.finalize', session.id), { preserveScroll: true, onSuccess: closeModal })`.
    - Disable submit while `processing` is true.
  - Do not close the modal when there are errors; only close on success.
  - After success, page shows the finalized badge/state; edit controls disabled for non‑admins.

Testing Notes

- Backend Feature tests (Pest/PHPUnit):
  - Finalize success with all fields present (200/redirect, db assertions for fields + state + timestamp, non‑admin lock).
  - Validation 422 per field; ensure no state change.
  - Unauthorized 403 when user cannot finalize.
  - Conflict 409 when already finalized.
- Frontend behavior (manual or automated):
  - Modal opens and blocks submit until all fields filled; errors render correctly.
  - Submit shows loading state; modal closes on success; page reflects finalized state.

Quick Commands

- Backend:
  - `php artisan make:request OSCE/FinalizeSessionRequest`
  - `php artisan make:controller OSCE/OsceSessionController`
  - `php artisan make:migration add_finalization_fields_to_osce_sessions_table`
  - `php artisan make:policy OsceSessionPolicy --model=OsceSession`
- Frontend:
  - Add `resources/js/Pages/OSCE/EndOsceModal.jsx` and integrate into session page.

