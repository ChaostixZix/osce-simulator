# Fix Assessment Flow — Diagnosis & AI Prompt

## Diagnosis
- Partial progress not visible: Backend `osce.status` already returns `progress_percentage`, `completed_areas`, `total_areas`, and `area_results` while in progress. The Results page didn’t merge `area_results` during processing, so users only saw a generic 1/5, 2/5 without details.
- Per-area fallback behavior: Already correct. If an AI call fails for a specific area, only that area falls back to rubric via `AreaAssessor::fallbackToRubric()`; other areas continue normally.
- Reassessment UX friction: Reassess button used a redirecting route (`osce.assess.trigger`) so the page didn’t receive immediate JSON queue state; users often navigated away/returned to see processing status.

## Implementation Summary (what to expect after fix)
- Results page merges `area_results` from `osce.status` into UI during processing so users see detailed area cards incrementally (1/5 → 2/5 → …) with scores/badges.
- Reassess button now posts to JSON API (`osce.assess`) with `{ force: true }` and reflects queue/processing state immediately on the same page.
- Per-area fallback remains scoped; no global fallback.

## AI Prompt — Per‑Area Scoring (JSON‑Strict)
Use this for each clinical area (history, exam, investigations, differential_diagnosis, management). The service enforces schema; this is the authoring guidance to keep outputs consistent and parseable.

You are an expert medical examiner assessing [AREA] performance for an OSCE session.

Rules:
- Output ONLY a JSON object; no markdown or commentary.
- Base the score STRICTLY on the evidence in the artifact (transcript, examinations, tests, timing, analysis fields).
- Keep justification concise, specific, and evidence‑based.

Return JSON with this exact shape:
{
  "score": <integer 0..MAX>,
  "max_score": MAX,
  "justification": "<<=1200 chars; specific, evidence‑based>",
  "outline": ["<bullet 1>", "<bullet 2>"],
  "citations": ["msg#12", "test:CBC", "exam:cardiac auscultation"]
}

Artifact (JSON):
<ARTIFACT_JSON_HERE>

Scoring hints (align MAX to area config):
- HISTORY (max 20): coverage of key points; systematic approach; clarity; quote questions and responses.
- EXAM (max 15): critical exams performed; systematic approach; documented findings.
- INVESTIGATIONS (max 20): appropriate/required tests ordered; cost awareness; avoided contraindications.
- DIFFERENTIAL DIAGNOSIS (max 15): reasoning quality; plausibility; completeness.
- MANAGEMENT (max 15): appropriateness; safety; follow‑up; rationale.

Important:
- Do not infer beyond the artifact—penalize missing evidence.
- Return valid JSON matching the schema precisely.
