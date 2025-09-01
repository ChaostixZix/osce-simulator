Goal
- Return structured outlines for each clinical area in OSCE assessment results.
- Provide clickable citations in assessment results that link to relevant sections.

Scope
- Backend: Adjust area-level AI prompts to include `outline` and `citations`. Parse and surface them in final results.
- Frontend: Render outlines as lists, render citations as clickable links when URLs are available, and add anchors for tests/messages/exams.
- Keep schema changes backward-compatible (no DB migration).

Update (fan-out + anchors + repair)
- Fan-out processing per clinical area with dedicated jobs; finalize aggregate when all areas done.
- Add examination and transcript anchors for clickable citations (`exam:<name>`, `msg#<n>`).
- Harden JSON repair to reduce unnecessary fallbacks.

Changes
- webapp/app/Services/AreaAssessor.php
  - Updated prompts for all areas (history, exam, investigations, differential diagnosis, management) to include:
    - `outline`: array of 4–8 concise bullet points summarizing the analysis.
    - `citations`: array of brief refs like `msg#12`, `test:ECG`, `exam:cardiac auscultation`.
  - No schema/validation changes required; additional fields are optional.

- webapp/app/Services/ResultReducer.php
  - When aggregating area results, parse `raw_response.text` JSON for `outline` and `citations`.
  - Added `normalizeCitations()` to convert strings into objects with `{ title, source, url }`:
    - `test:<name>` -> `#test-<slug>`
    - `msg#<n>` -> `#msg-<n>`
    - `exam:<name>` -> `#exam-<slug>`
  - Attach `outline` and normalized `citations` to each `clinical_areas[]` entry in `final_result`.

- webapp/resources/js/pages/OsceResult.jsx
  - Prefer `assessment.output.clinical_areas` if available for rendering areas.
  - Render new `outline` list under each area’s assessment.
  - Render `citations` robustly (supports both string and object forms); hyperlinks when `url` is present.
  - Add `id` anchors to each test item as `test-<slug>` so `test:<name>` citations scroll to the correct card.
  - Add Transcript section (first 100 messages) with `msg-<n>` anchors pulled from `osce.chat.history`.
  - Add Examinations Summary with `exam-<slug>` anchors from `session.examinations`.
  
- webapp/app/Http/Controllers/OsceAssessmentController.php
  - Load and include `examinations` in the `session` payload for the results page.

- webapp/app/Jobs/AiAssessorOrchestrator.php
  - Dispatch one `AssessAreaJob` per area (independent processing).
  - Dispatch `FinalizeAssessmentRunJob` to aggregate results when all areas complete.

- webapp/app/Jobs/AssessAreaJob.php (new)
- webapp/app/Jobs/FinalizeAssessmentRunJob.php (new)

- webapp/app/Services/AreaAssessor.php
  - JSON repair: trim to last brace, balance braces, remove trailing commas.

- webapp/app/Services/AiAssessorService.php
  - Harden `repairJsonResponse` similarly; add object extraction fallback.
  - When fetching results via AJAX, normalize `assessor_output` to `output` in component state.

Validation
- Trigger an assessment and open the OSCE results page:
  - Verify each clinical area may show an Outline section with bullet points.
  - Verify References show and links are clickable when present.
  - Scroll behavior: `test:<name>`, `exam:<name>`, and `msg#<n>` links jump to the corresponding anchored sections.
  - AJAX refresh (while processing) continues to render from `output.clinical_areas` once available.

Notes
- No DB schema changes; outline/citations are derived from AI JSON and surfaced in `final_result`.
- Message/exam anchors are now present via Transcript and Examinations sections.
- Prompts remain backward compatible: only required fields are enforced.
