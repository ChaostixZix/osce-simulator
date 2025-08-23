# Implementation Report — osce-session-assessment-results

| Area | Changes | Files |
|---|---|---|
| Migration | Add assessor fields to `osce_sessions`: `assessor_payload` json, `assessor_output` json, `assessed_at` timestamp, `assessor_model` string, `rubric_version` string; ensure `max_score` exists. | `webapp/database/migrations/*_alter_osce_sessions_add_assessor_fields.php` |
| Config | Add rubric weights/penalties and version. | `webapp/config/osce_scoring.php` |
| Model | Dispatch `AssessOsceSessionJob` from `OsceSession::markAsCompleted()` if not yet assessed; add casts for new json fields. | `webapp/app/Models/OsceSession.php` |
| Service | Build artifact, compute deterministic `computed_scores`, call Gemini with strict JSON schema, validate/repair fallback, persist outputs. | `webapp/app/Services/AiAssessorService.php` |
| Job | Background assessment execution, idempotent with `assessed_at` unless `force`. | `webapp/app/Jobs/AssessOsceSessionJob.php` |
| Controller | Manual assess and results APIs, plus Inertia page action. | `webapp/app/Http/Controllers/OsceAssessmentController.php` |
| Routes | Auth-protected: POST `/api/osce/sessions/{session}/assess`, GET `/api/osce/sessions/{session}/results`, GET `/osce/results/{session}`. | `webapp/routes/web.php` |
| Frontend | Results page: rubric table, AI comment, red flags, citations with anchors to snippets; admin “Reassess”. | `webapp/resources/js/pages/OsceResult.vue` |
| Tests | Unit: scoring; Feature: job + persistence; API: permissions and schema; Fallbacks. | `webapp/tests/Feature/OsceAssessmentTest.php` |

Notes
- AI call uses existing Gemini API configuration style from `AiPatientService`; `temperature=0` and strict JSON response.
- Assessment artifact persists verbatim for audit/replay; AI commentary never modifies numeric score.
- Owner/admin authorization enforced for assess/results endpoints.
- If no `GEMINI_API_KEY`, service produces rubric-only output and marks `model_info.status = 'unavailable'`.

# Diagnosis: Build OSCE Session Assessment Results (Laravel + Vue 3 + Inertia) — add deterministic rubric scoring, AI doctor-style commentary with strict JSON schema, background job, APIs, and an Inertia results page

Shared slug: osce-session-assessment-results

## Purpose

OSCE requires standardized, reproducible assessment after a session ends. We need a hybrid pipeline that: (1) computes a deterministic rubric score locally from objective actions (history-taking signals, physical exam actions, tests ordered, costs, missed required tests, time use/safety), and (2) generates concise, “doctor-style” comments via Gemini with a strict JSON schema. The goal is defensible, auditable results: local numeric scoring is authority; AI adds structured feedback citing specific session artifacts (message IDs, test names) without affecting the numeric score.

## Scope

- Inputs: a completed `OsceSession` with relationships `osceCase`, `chatMessages`, `orderedTests`, `examinations` and timing (`started_at`, `duration_minutes`, `time_extended`).
- Processing:
  - Build an immutable “assessment artifact” from the session: bounded chat slice, actions, costs, time metrics, case checklist/objectives/required tests.
  - Compute local rubric scores using config weights, case lists (e.g., `required_tests`, `highly_appropriate_tests`, `contraindicated_tests`), and timing.
  - Call Gemini at temperature=0 with a constrained prompt to produce a strict JSON output with per-criterion commentary and citations to the artifact.
- Outputs (persisted on session): `score`, `max_score`, `assessor_payload` (artifact JSON), `assessor_output` (AI JSON), `assessed_at` (timestamp), `assessor_model` (string), `rubric_version` (string). Do not modify `started_at` or timer fields.
- Side-effects: Queue assessment on completion/expiry; allow manual reassessment (owner/admin). Expose an Inertia page to view results and a JSON API to fetch them.

## Code References (existing)

- Models
  - `webapp/app/Models/OsceSession.php` — status, timing, relationships, `markAsCompleted()`
  - `webapp/app/Models/OsceCase.php` — case meta, checklists, test lists, AI profile
  - `webapp/app/Models/OsceChatMessage.php` — chat log
  - `webapp/app/Models/SessionOrderedTest.php`, `SessionExamination.php` — actions taken
- Controllers & routes
  - `webapp/app/Http/Controllers/OsceController.php` — session lifecycle; completion; clinical reasoning endpoints
  - `webapp/app/Http/Controllers/OsceChatController.php` — chat storage
  - `webapp/routes/web.php` — OSCE routes
- Services
  - `webapp/app/Services/AiPatientService.php` — Gemini usage (reuse config style)

## Constraints

- Use Inertia flows; add a dedicated results page `OsceResult.vue` and JSON endpoints.
- Keep numeric scoring fully local and deterministic; AI output must not change score.
- Gemini config: reuse `config('services.gemini')`; infer model name from `GEMINI_MODEL` or default; set low variance: `temperature: 0`, `topK: 1`, `topP: 1`, concise `maxOutputTokens`.
- Strict JSON only from AI (no prose), with schema validation server-side; one repair attempt on parse failure.
- No chain-of-thought. Require concise justifications and explicit citations to artifact items.
- Security: owner or admin can view/trigger assessment for a session. Do not expose raw chat publicly.
- Performance: limit chat slice to last 30 messages (or by tokens) to bound context.

## Error Handling

- No API key configured → compute rubric + generate template comments; store banner `ai_unavailable` in `assessor_output.model_info.status`.
- JSON invalid from AI → retry once with “repair JSON” instruction; on failure fall back to rubric-only commentary.
- Unauthorized or non-owner access → 403/404.
- Reassessment idempotency → if `assessed_at` exists and `force` not set, skip.

## Acceptance Criteria

1. Completing or expiring a session queues an assessment job and results become available within seconds.
2. `OsceSession` stores `score`, `max_score`, `assessor_payload`, `assessor_output`, `assessed_at`, `assessor_model`, `rubric_version` safely.
3. Results page shows per-criterion scores/max and the AI “doctor comment” with citations linking to chat/test/exam entries.
4. Manual reassess endpoint works for owner/admin; includes a force flag to overwrite previous results.
5. AI output is valid JSON matching the schema; local rubric scoring matches config weights for golden fixtures.
6. If AI is unavailable or JSON is invalid, the page still shows deterministic scores and templated feedback.
7. API: `GET /api/osce/sessions/{session}/results` returns JSON (owner/admin).
8. All times honor existing timer logic; no change to `started_at`; expired sessions cannot re-open chat.

## Out of Scope

- Changing the OSCE chat behavior, patient generation, or clinical reasoning scoring logic already present.
- Export/print, analytics dashboards, or external grading integrations.
- Multi-rater aggregation; this is single-assessor (AI) commentary over deterministic rubric.

## Rubric and Weights

Add `config/osce_scoring.php` with versioned weights and keys:

```php
return [
  'rubric_version' => 'RUBRIC_V1.0',
  'criteria' => [
    ['key' => 'history', 'label' => 'History-taking', 'max' => 20],
    ['key' => 'exam', 'label' => 'Physical Exam', 'max' => 15],
    ['key' => 'investigations', 'label' => 'Investigations', 'max' => 20],
    ['key' => 'diagnosis', 'label' => 'Diagnosis & Reasoning', 'max' => 20],
    ['key' => 'management', 'label' => 'Management Plan', 'max' => 15],
    ['key' => 'communication', 'label' => 'Communication/Professionalism', 'max' => 5],
    ['key' => 'safety', 'label' => 'Time Use/Safety', 'max' => 5],
  ],
  'penalties' => [
    'contraindicated_test' => 5,
    'inappropriate_test' => 2,
    'missed_required_test' => 3,
    'over_budget' => 2,
    'unsafe_statement' => 3,
  ],
];
```

## Data Model Changes

- Migration on `osce_sessions`:
  - `assessor_payload` json nullable
  - `assessor_output` json nullable
  - `assessed_at` timestamp nullable
  - `assessor_model` string nullable
  - `rubric_version` string nullable
  - Ensure `score` and `max_score` are present (if not, add)

## Endpoints & Pages

- POST `/api/osce/sessions/{session}/assess` — manual trigger (owner/admin). Body: `{ force?: boolean }`.
- GET `/api/osce/sessions/{session}/results` — returns persisted results JSON (owner/admin).
- GET `/osce/results/{session}` — Inertia page `OsceResult.vue` for human-readable view; link from OSCE dashboard for completed sessions.

## Job & Service

- Job `AssessOsceSessionJob implements ShouldQueue`:
  - Loads session + relations; if `status!==completed` and `!is_expired`, early return (or soft-complete then assess for expired).
  - Calls `AiAssessorService->assess($session)`; persists outputs; sets `assessed_at`.
  - Idempotent based on `assessed_at` unless `force`.
- Service `AiAssessorService`:
  - Builds artifact: { case: subset, rubric_version, transcript: last 30 msgs with `id`, `sender_type`, `text`, actions: tests/exams with timestamps, costs and `case_budget`, timing: started_at/duration/elapsed, known checklists and required tests }.
  - Computes deterministic rubric subscores and total (authoritative numeric score) using config + case lists.
  - Calls Gemini with `response_mime_type: application/json` (if supported) or instructs “return strict JSON only”.
  - Validates JSON by schema; retries with repair if needed; falls back to rubric-only comments otherwise.

## Gemini Assessor Prompt (exact; implement in service)

System Role (conceptual model — serialize into a single `text` field for Gemini if system role unsupported):

```
You are an experienced physician examiner conducting an OSCE assessment. Produce concise, structured feedback.
Rules:
- Output MUST be a single JSON object and nothing else.
- Do NOT include chain-of-thought. Provide brief justifications with direct citations to the provided artifact only.
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps if applicable.
```

User Content Template (variables in `{{double_curly}}`):

```
Artifact:
{{artifact_json}}

Rubric (version {{rubric_version}}):
{{rubric_json}}

Task:
Return strict JSON matching this TypeScript schema:

type Assessment = {
  rubric_version: string;
  criteria: Array<{
    key: 'history'|'exam'|'investigations'|'diagnosis'|'management'|'communication'|'safety';
    score: number; // 0..max from local rubric computation (provided in artifact under computed_scores)
    max: number;
    justification: string; // 1–3 sentences, cite artifact ids
    citations: string[]; // e.g., ["msg#12","lab:Troponin","exam:respiratory.auscultation"]
  }>;
  overall_comment: string; // ≤ 120 words; actionable; professional tone
  red_flags: string[]; // unsafe actions or critical misses
  model_info: { name: string; temperature: number; };
}

Important:
- Use the provided `computed_scores` inside the artifact for `criteria[i].score`. Do NOT invent scores.
- Justifications must reference `citations` from the artifact (message ids, test names, exam keys).
- Output ONLY the JSON object.
```

Generation config for assessor call:

```json
{
  "generationConfig": { "temperature": 0, "topK": 1, "topP": 1, "maxOutputTokens": 700 }
}
```

## Implementation Steps (do exactly this)

1) Migrations
   - Create migration to add `assessor_payload`, `assessor_output`, `assessed_at`, `assessor_model`, `rubric_version`, and (if missing) `max_score` to `osce_sessions`.
   - Run `php artisan migrate`.

2) Config
   - Add `config/osce_scoring.php` as above; read weights and penalties in service.

3) Service: `App/Services/AiAssessorService.php`
   - `assess(OsceSession $session, bool $force = false): OsceSession` — builds artifact, computes `computed_scores`, calls Gemini, validates JSON, persists.
   - `buildArtifact(OsceSession $session): array` — slice chat to last 30 messages with compact fields `{id,sender_type,text}`, include tests/exams/cost/timing/case lists.
   - `computeScores(OsceSession $session, array $config): array` — history/exam/investigations/diagnosis/management/communication/safety scoring from artifact and case lists; penalties: contraindicated/inappropriate/missed/over_budget.
   - `callGemini(array $artifact, array $computedScores, array $config): array` — returns parsed JSON or throws.

4) Job: `App/Jobs/AssessOsceSessionJob.php`
   - `handle(): void` — loads session, early-return if unauthorized state, calls service, logs latency.

5) Model hook
   - In `OsceSession::markAsCompleted()`, after save, dispatch `AssessOsceSessionJob` (queue) if not already assessed.

6) Controller & Routes
   - New `OsceAssessmentController`:
     - `assess(OsceSession $session, Request)` POST → authorize, dispatch job (or run sync in dev), return JSON.
     - `results(OsceSession $session)` GET → returns JSON `{ score, max_score, assessor_output, assessed_at, rubric_version }`.
     - `show(OsceSession $session)` GET → Inertia `OsceResult` page for human-readable view; link from OSCE dashboard for completed sessions.
   - Add routes in `webapp/routes/web.php` under auth group.

7) Frontend: `resources/js/pages/OsceResult.vue`
   - Render rubric table (criteria rows with score/max), red flag chips, and overall comment.
   - Show citations as interactive anchors resolving to snippet modals for messages/tests/exams.
   - Admin-only “Reassess” button (POST) and “Assessed at” timestamp + model/version badges.

8) Tests (Pest)
   - Unit: score computation from synthetic artifact (no AI).
   - Feature: completing a session enqueues job, persists assessor fields, view results.
   - API: results JSON schema and permissions.
   - Fallbacks: no API key → rubric-only; malformed AI → fallback path.

## File Impact Summary

- New: `app/Services/AiAssessorService.php`, `app/Jobs/AssessOsceSessionJob.php`, `app/Http/Controllers/OsceAssessmentController.php`
- Update: `app/Models/OsceSession.php` (dispatch job on complete), `routes/web.php`
- New: `config/osce_scoring.php`
- New: `resources/js/pages/OsceResult.vue`
- Migration: alter `osce_sessions`

## Quick Commands

```bash
php artisan make:job AssessOsceSessionJob
php artisan make:controller OsceAssessmentController
php artisan make:test OsceAssessmentTest --pest
```
