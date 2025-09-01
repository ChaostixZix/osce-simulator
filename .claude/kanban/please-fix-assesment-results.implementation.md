Title: Fix OSCE assessment results not showing

Goal
- Ensure the OSCE results page displays computed scores correctly after an assessment completes.

Scope
- Frontend only change in `webapp/resources/js/pages/OsceResult.jsx`.
- No schema or API changes.

Root Cause
- The page expected `overall_score` from the API, but the controller returns `score` and `max_score`.
- The header read `session.osce_case.title`, while the controller provides the case as `session.case.title`.

Changes
- File: `webapp/resources/js/pages/OsceResult.jsx`
  - Use `session?.case?.title` for the page title instead of `session?.osce_case?.title`.
  - Compute overall percentage from `currentAssessmentData.score` and `currentAssessmentData.max_score` with safe guards against divide-by-zero.
  - Feed the computed percentage into `getBand(...)`.
  - Show clinical reasoning big number from `session.clinical_reasoning_score` (consistent with the data provided with the page).

Validation Steps
- Start an OSCE session, complete rationalization, and run assessment.
- Visit `osce/results/{session}`.
- Expect:
  - Title shows the case title.
  - Overall score shows a valid percentage (no NaN) and an appropriate band label.
  - Clinical reasoning shows the session points (percentage label retained for consistency with existing UI).
  - Other sections (areas, telemetry) continue to render when provided.

Notes
- The JSON API at `GET api/osce/sessions/{session}/results` also returns `score` and `max_score`. The fix works both for initial Inertia props and subsequent fetch updates.
- No DB migrations or environment changes required.
