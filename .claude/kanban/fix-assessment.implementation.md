Title: Fix assessment queue + status stream fallback warning

Goal
- Ensure assessments are enqueued and processed using the queue without enum mismatches.
- Prevent status-stream network errors caused by invalid enum values.
- Show a clear warning message when assessment is still in progress.

Status
- Implemented. Code paths, routes, and UI verified in repo. Queue is asynchronous using the `database` driver.

Scope of Changes
- Database migration: align `ai_assessment_runs.status` enum values with code usage (`queued`, `in_progress`, `completed`, `failed`, `cancelled`).
- Model: allow queue tracking fields to be mass-assigned and casted.
- Frontend: display an in-progress warning on OSCE results view.

Files Changed
- webapp/database/migrations/2025_08_29_014752_create_ai_assessment_runs_table.php
  - Update enum values to include `queued` and `cancelled`. Set default to `queued`.
- webapp/app/Models/AiAssessmentRun.php
  - Add queue fields to `$fillable`: `queue_position`, `estimated_wait_time_minutes`, `queued_at`, `current_area`, `status_message`.
  - Add `$casts['queued_at' => 'datetime']`.
- webapp/resources/js/pages/OsceResult.jsx
  - Fix prop name to receive `assessment` (matches controller) and add a yellow warning banner when status is `queued` or `in_progress`.
 - webapp/app/Jobs/AiAssessorOrchestrator.php
   - Mark job to run on `assessments` queue (async) via `$queue = 'assessments'`.
 - webapp/app/Jobs/AssessOsceSessionJob.php
   - Mark job to run on `assessments` queue (async) via `$queue = 'assessments'`.

Asynchronous Queue
- Driver: `database` (see `config/queue.php`, `.env.example QUEUE_CONNECTION=database`).
- Worker: `composer run dev` now runs `queue:work --queue=assessments,default --tries=1` alongside the server.
- Dispatching: Jobs are routed to `assessments` via `->onQueue('assessments')` at dispatch time.

Routes (Existing)
- `GET api/osce/sessions/{session}/status` → `OsceAssessmentController@status` as `osce.status`.
- `GET api/osce/sessions/{session}/status-stream` → `AssessmentStatusController@stream` as `osce.status.stream`.
- `POST osce/sessions/{session}/assess/trigger` → `OsceAssessmentController@assessInertia` as `osce.assess.trigger`.

Validation Steps
1) Run migrations:
   - cd webapp && php artisan migrate
2) Start dev stack (already configured to run queue listener):
   - composer run dev (or run server + queue:listen separately)
3) Complete a session, trigger assessment via UI or API:
   - POST `osce/sessions/{session}/assess/trigger`
4) Observe:
   - SSE endpoint `api/osce/sessions/{session}/status-stream` returns events without 500 errors.
   - QueueIndicator shows queued/in-progress states.
   - Warning banner appears: "The assessment is still in progress, please come back later."
   - On completion, status flips to completed and the banner disappears.

Notes
- The backend previously used `queued` statuses while the base migration only allowed `pending`, causing DB errors and downstream SSE failures. This is resolved by aligning enum values.
- The results page uses a different data shape for rich details; this change focuses on queue status/warning as requested without restructuring the results layout.
