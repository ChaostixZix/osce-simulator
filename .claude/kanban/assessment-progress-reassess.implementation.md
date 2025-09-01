Title: Live Assessment Progress + Smooth Reassessment UX

Goal
- Send partial results to the frontend as soon as each clinical area completes (e.g., 1/5 → 2/5) so users can see details without waiting for the full run.
- Ensure reassessment can be triggered from results page and immediately shows queue/processing status without navigating back to the OSCE station.
- If an area fails AI evaluation, only that specific area falls back to rubric-based scoring.

Diagnosis
- Backend already supports per-area fan-out via `AiAssessorOrchestrator` + `AssessAreaJob` and stores progress in `AiAssessmentRun` and `AiAssessmentAreaResult`.
- `status` API already includes `progress_percentage`, `completed_areas`, `total_areas`, and – when `in_progress` – `area_results` with per-area status and scores.
- Frontend `OsceResult.jsx` only fetched full results after completion and ignored the progressive `area_results` from `/api/osce/sessions/{session}/status`.
- Reassessment button posted to `osce.assess.trigger` (redirect/back response), so the page had no immediate JSON status to render and often required leaving/returning to see updates.
- Fallback-to-rubric is already scoped per-area in `AreaAssessor::assessArea()` → `fallbackToRubric()`; finalize job aggregates completed + fallback areas. No global fallback is performed.

Changes Implemented
- File: `webapp/resources/js/pages/OsceResult.jsx`
  - Merge progressive `area_results` from status polling into component state so the UI shows detailed cards during processing (e.g., 1/5 complete with details).
  - Rewired Reassess button to call JSON API `route('osce.assess', session.id)` with `{ force: true }`, parsing response and reflecting live queue state immediately.

Validation Steps
- Start an assessment (or trigger reassessment) from Results screen.
- Observe the QueueIndicator showing `queued` → `in_progress`.
- As each area finishes, the “Clinical areas assessment” section should populate incrementally (1/5, 2/5, …) with status badges and scores.
- On API/network errors for one area, that area should display `Rubric` badge, others continue normally.
- When all areas complete, the page shows the aggregated total and the final AI output.

Notes
- No migrations needed. Existing per-area fallback logic satisfies the requirement “only 1 area fallback to rubric when it fails.”
- WebSocket completion notifications still work for final completion; progressive updates rely on polling the status endpoint.

Appendix — AI Prompt (Area Scoring, JSON-Strict)
Use this prompt for each clinical area to return a strict JSON object. Match to area and `max_score` accordingly. The service already enforces schema; this is the authoring guidance.

You are an expert medical examiner assessing [AREA] performance for an OSCE session.

Rules:
- Output ONLY a JSON object; no markdown or commentary.
- Base the score STRICTLY on the evidence in the provided artifact (transcript, examinations, tests, timing, and analysis fields).
- Keep justification concise, specific, and evidence-based.

JSON schema to return:
{
  "score": <integer 0..MAX>,
  "max_score": MAX,
  "justification": "<<=1200 chars, evidence-based with specifics>",
  "outline": ["<bullet 1>", "<bullet 2>"],
  "citations": ["msg#12", "test:CBC", "exam:cardiac auscultation"]
}

Artifact (JSON):
<ARTIFACT_JSON_HERE>

Scoring guidance examples:
- HISTORY (max 20): coverage of key points, systematic approach, clarity; quote questions and responses.
- EXAM (max 15): critical exams performed, systematic approach, documented findings.
- INVESTIGATIONS (max 20): appropriate/required tests ordered, cost awareness, avoided contraindications.
- DIFFERENTIAL DIAGNOSIS (max 15): reasoning quality, plausibility, completeness.
- MANAGEMENT (max 15): appropriateness, safety, follow-up, rationale.

Important:
- Do not infer beyond the artifact; if missing, penalize accordingly.
- Return valid JSON that matches the schema precisely.

