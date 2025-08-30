Feature slug: end-osce-require-diagnosis-plan

Implementation Report

- Backend
  - Migration: add `diagnosis` (text), `differential_diagnosis` (text), `plan` (text), ensure `state` enum[`draft`,`finalized`] with default `draft`, and `finalized_at` timestamp nullable on `osce_sessions`.
  - Policy: `OsceSessionPolicy@finalize` allows admin or session owner while `state==='draft'`.
  - Request: `OSCE/FinalizeSessionRequest` with rules for required strings on all three fields.
  - Route: `POST /api/osce/sessions/{session}/finalize` named `osce.sessions.finalize` mapped to `OSCE/OsceSessionController@finalize`.
  - Controller: `finalize()` authorizes, validates, checks conflict if already finalized, updates fields, sets `state='finalized'`, `finalized_at=now()`, redirects with flash.

- Frontend (React + Inertia)
  - Component: `resources/js/Pages/OSCE/EndOsceModal.jsx` created.
  - Session page: added “End OSCE” button to open modal; integrated with `useForm` and route `osce.sessions.finalize`.
  - UX: Required indicators, inline errors, disabled submit while processing, modal closes on success only.

Files Changed

- webapp/database/migrations/xxxx_xx_xx_xxxxxx_add_finalization_fields_to_osce_sessions_table.php — new
- webapp/app/Models/OsceSession.php — ensure fillable fields/state
- webapp/app/Policies/OsceSessionPolicy.php — update finalize rule
- webapp/app/Http/Requests/OSCE/FinalizeSessionRequest.php — new
- webapp/app/Http/Controllers/OSCE/OsceSessionController.php — add finalize()
- webapp/routes/web.php or routes/api.php — add finalize route
- webapp/resources/js/Pages/OSCE/EndOsceModal.jsx — new
- webapp/resources/js/Pages/OSCE/SessionPage.jsx — integrate modal trigger and finalized state UI

Notes

- Keep Inertia usage consistent with repository conventions; no raw fetch.
- Respect Asia/Jakarta timezone in any timestamps displayed.
- Ensure non‑admins cannot update finalized sessions, with admin override when required by policy.

