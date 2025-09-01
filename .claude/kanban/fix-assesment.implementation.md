Title: Fix assessment results visibility + unify prompts

Goal
- Ensure OSCE assessment results render reliably after completion.
- Merge the new area-by-area prompt format with the legacy expectations (outline/citations), without breaking existing flows.

Scope
- Frontend results page: confirm correct prop names and safe percentage calculation.
- Backend area assessor: allow richer JSON fields (outline, citations) to pass through schema and prompts.

Changes
- webapp/resources/js/pages/OsceResult.jsx
  - Already expects `assessment` prop and computes percentage from `score`/`max_score` with divide-by-zero guard.
  - Title uses `session.case.title` (matches controller payload).
  - Shows in-progress banner and queue indicator.

- webapp/app/Services/AreaAssessor.php
  - Updated `getAreaSchema()` to accept optional `outline: string[]` and `citations: string[]`, and allow `additionalProperties`.
  - Updated `buildGenericPrompt()` to request `outline` and `citations` alongside `score`, `max_score`, `justification`.
  - This preserves strict required fields while enabling richer output that `ResultReducer` can surface.

Context
- Queue orchestration and controller responses are already aligned:
  - Jobs dispatched to `assessments` queue.
  - Status and results endpoints return consistent keys (`status`, `score`, `max_score`, `assessor_output`, `area_results`).
  - Inertia page gets `assessment` with `output` = final result when available.

Validation
- Start an OSCE session, complete rationalization, trigger assessment.
- Visit `osce/results/{session}`:
  - If processing: see yellow warning and queue indicator.
  - On completion: see overall score percentage and band; clinical areas render from `assessment.output.clinical_areas` or `area_results`.
  - If model returns `outline`/`citations`, they appear under each area (via reducer parsing).

Notes
- No DB schema change required for this fix; enum/status alignment already present in migrations.
- The area schema change is backward compatible (only adds optional fields).

Debugging Enhancements
- Added per-area error capture on AI failures (`error_message` on `AiAssessmentAreaResult`).
- Exposed `error_message` to API and Inertia page for visibility.
- UI now shows a compact debug panel when fallbacks occur, listing area status, attempts, and last error.
- New command: `php artisan ai:probe {sessionId} --areas` to directly call Gemini with the session’s artifact and print raw response previews per clinical area.
