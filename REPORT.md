# OSCE Rationalization Flow — Implementation Report

This report summarizes what was implemented to enable the OSCE flow: Rasionalisasi (post‑session reflection) → View Results → Scoring, what should be working now, and what is not implemented yet.

## What’s Implemented (Should Be Working)
- Rationalization as a post‑session step
  - New page: `GET /osce/rationalization/{session}` shows a reflection summary (reasoning score, total test cost, evaluation feedback) and a button to complete the rationalization.
  - New action: `POST /api/osce/sessions/{session}/rationalization/complete` sets `rationalization_completed_at` and redirects to results.
  - Database: new column `osce_sessions.rationalization_completed_at` (nullable `timestamp`).

- Single source of truth for gating
  - Results (page and JSON) are viewable only when `rationalization_completed_at` is set.
  - Access to results before rationalization completion redirects to the rationalization page with a warning message.
  - Source: `OsceSession::getIsRationalizationCompleteAttribute()`.

- Dashboard CTAs reflect the flow
  - In progress → “Continue” (goes to `/osce/chat/{session}`).
  - Completed but not rationalized → “Rasionalisasi” (goes to `/osce/rationalization/{session}`) and “View Results” disabled with helper text.
  - Completed and rationalized → “View Results” enabled.
  - These booleans are computed server‑side and passed to Inertia props: `canRationalize`, `canViewResults`, `canProceedToScoring`.

- Results page: Clinical Reasoning section
  - Dedicated card rendering the AI clinical‑reasoning area (or fallback) and showing rationalization contributions (`clinical_reasoning_score`, `total_test_cost`, `evaluation_feedback`).
  - Quick jump button “Go to Clinical Reasoning”.

- AI assessment fallback (no Gemini key / API error)
  - If Gemini is not configured or returns an error, the backend computes rubric‑based scores (using `config/osce_scoring.php`) and persists a structured `assessor_output` so the Results page always has content.
  - You can force re‑assessment from the Results page (Reassess button) which re‑runs assessment and reloads the page.

- Routes (visible via `php artisan route:list`)
  - `GET  /osce` (dashboard)
  - `GET  /osce/chat/{session}` (live session)
  - `GET  /osce/rationalization/{session}` (post‑session reflection)
  - `POST /api/osce/sessions/{session}/rationalization/complete`
  - `GET  /osce/results/{session}` (guarded by rationalization)
  - `GET  /osce/scoring/{session}` (alias → same view/guard)
  - `GET  /api/osce/sessions/{session}/results` (JSON; guarded)

## Files Changed / Added
- Backend
  - `app/Models/OsceSession.php`: added `rationalization_completed_at` cast and `is_rationalization_complete` accessor.
  - `database/migrations/2025_08_23_000007_add_rationalization_fields_to_osce_sessions_table.php`: adds the column.
  - `app/Http/Controllers/OsceRationalizationController.php`: new controller for rationalization show/complete.
  - `app/Http/Controllers/OsceAssessmentController.php`: gating updated to use `is_rationalization_complete` and redirect to rationalization page when not complete; includes rationalization metrics in props.
  - `app/Http/Controllers/OsceController.php`: adds server booleans (`canRationalize`, `canViewResults`, `canProceedToScoring`).
  - `app/Services/AiAssessorService.php`: fallback scoring when Gemini unavailable or errors; splits Clinical Reasoning and Diagnosis in the detailed areas prompt.
  - `routes/web.php`: adds rationalization routes and scoring alias.
  - Tests: `tests/Feature/OsceResultsGatingTest.php` updated to reflect the new gating and rationalization completion flag.

- Frontend (Vue/Inertia)
  - `resources/js/pages/Osce.vue`: CTA logic for Continue / Rasionalisasi / View Results, helper text when results are disabled.
  - `resources/js/pages/OsceRationalization.vue`: new page for post‑session rationalization with “Selesaikan Rasionalisasi” button.
  - `resources/js/pages/OsceResult.vue`: dedicated “Clinical Reasoning” section, quick jump button, and renders rationalization metrics from server props.

## Required Post‑Update Steps
- Clear route cache (so new routes are picked up):
  - `cd webapp && php artisan route:clear`
- Run migrations (adds `rationalization_completed_at`):
  - `cd webapp && php artisan migrate`
- Run dev stack:
  - `cd webapp && composer dev` (or `php artisan serve` + `npm run dev`)

## Expected User Flow
1) Start and complete an OSCE session.
2) On `/osce` (dashboard):
   - If the session is in progress → “Continue”.
   - If the session is completed but not rationalized → “Rasionalisasi” (click to open the rationalization page).
3) On the rationalization page, click “Selesaikan Rasionalisasi”.
4) Results become available; “View Results” opens the assessment page with the Clinical Reasoning section.
5) Optional: “Reassess” on results page to force reassessment (fallback or AI depending on env).

## Works With / Without Gemini
- Without Gemini: Fallback rubric‑based scoring populates scores and narrative; Results page renders fully.
- With Gemini: Set `GEMINI_API_KEY` and `GEMINI_MODEL` in `webapp/.env`; background job or local mode will produce AI analysis.

## Not Implemented Yet / Known Limitations
- Read‑only chat history view after completion (currently chat route redirects once locked). If desired, we can add a read‑only transcript view.
- Rich rationalization form (current page presents summary + “complete” action; no additional fields are collected).
- End‑to‑end UI tests (Pest covers backend gating; no Cypress/Playwright tests in this repo).
- Comprehensive admin tooling (e.g., rationalization resets or reporting) not included.
- If you had existing assessed sessions with null scores, they will stay until you click “Reassess” (or call the assess API with `{ force: true }`).

## Quick Verification Checklist
- Dashboard shows correct CTAs per session state.
- `/osce/rationalization/{id}` loads for completed sessions; completing it enables results.
- `/osce/results/{id}` loads only after rationalization completion.
- Results page shows Clinical Reasoning section and rationalization metrics.
- Assessment works without Gemini (fallback rubric scores present). With Gemini configured, AI narrative appears.

