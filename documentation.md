# OSCE Simulator documentation

## Supported public behavior

The landing page explains the station and its limits. A visitor opens /demo, starts the encounter, asks the scripted patient questions, requests recorded findings, submits a clinical summary and receives a persisted evidence review. No account or provider credential is required.

The original Chest Pain Assessment station is the public entry point. DemoSeeder reuses OsceCaseSeeder idempotently. It does not overwrite an existing case. No station text, checklist, authored reply, investigation value or scoring definition was rewritten.

## Architecture

Laravel routes and GuestStationController own validation, session ownership and persistence. React pages under resources/js/pages/Guest use Inertia useForm for mutations. StationLayout and guest.css provide shared, scoped presentation; see DESIGN.md.

A random token stays in the encrypted server session. Only its hash is stored on OsceSession. Guest reads and writes compare that hash; another browser receives 404. The client receives explicitly selected props, never the ownership token or the complete case answer bank.

Message requests use a UUID for retry deduplication. Session-blocking middleware serializes requests within a browser; database transactions protect messages, findings and completion. Separate rate-limit buckets prevent history-taking from exhausting summary submission attempts.

The continuous timer derives from started_at. Refreshing does not reset it. Expiry blocks further questions and findings while allowing summary submission. Completed encounters cannot be overwritten; repeating starts a new encounter.

### Patient

LocalPatientService matches supported English question topics against authored ai_patient_responses, profile and symptoms. Missing facts return an explicit unavailable reply. It cannot interpret arbitrary natural language reliably and is not a clinician or a medical advice service.

The guest controller always uses this local service, even if AI_PROVIDER changes. Legacy AiPatientService defaults to local and constructs remote providers only when explicitly selected.

### Review

EvidenceReviewService records six supported discussion topics with actual question/reply pairs, requested findings, submitted diagnosis/differentials/plan and the original examiner checklist. It does not score competence or validate the submitted clinical decisions.

Guest completion persists the review in the same transaction as the summary. Legacy assessment dispatch now waits for finalization, and its artifact includes the submitted summary. Existing numeric rubric definitions remain unchanged and are not used for guest feedback.

## Configuration and persistence

Local and single-host showcase defaults: SQLite, encrypted file sessions, file cache, synchronous queue and log broadcasting. The native production service uses /var/lib/osce/database.sqlite and /var/lib/osce/storage, separate from local QA data. SQLite is suitable for this small, single-host showcase; load capacity is unmeasured. Use PostgreSQL and shared session/cache storage before horizontal scaling.

APP_KEY must be generated once, kept secret and backed up with the database. Production sets APP_DEBUG=false, APP_URL/ASSET_URL to https://osce.bintangputra.dev and secure cookies. The public origin is same-origin; no cross-origin API or CORS workaround is needed.

Guest records older than seven days are removed by `php artisan osce:prune-guests`. Production installs a daily systemd timer for this command. Browser session access expires after two hours of inactivity by default. Losing the browser session loses access to a saved guest review.

Never enter real patient information. Backups and logs require their own retention policy; do not retain guest-content backups beyond the stated window.

## Optional services and credentials

None of these is necessary for the public station:

| Feature | Required service/configuration |
| --- | --- |
| Account login | WorkOS API key, client ID, callback URL at /authenticate |
| Gemini patient/assessment features | Gemini API key, supported model and billing/quota |
| Azure provider features | Azure API key, endpoint, deployment/model and quota |
| Async assessment and broadcasts | Queue worker, Redis if selected, Laravel Reverb credentials and process |
| Email delivery | SMTP/provider credentials; default log mail sends nothing |
| Remote storage/image tools | Their configured provider credentials and storage, with explicit cost controls |
| Larger production database | PostgreSQL host/database/user/password and backups |

A real AI key enables explicitly selected legacy provider calls. It does not alter the public guest path or establish clinical validity. WorkOS, remote AI, email delivery, voice, visualizations, coaching, replay and growth integrations were not exercised against paid/live providers.

## Development and checks

Use README.md from the repository root. Bun is not installed on the restoration host; npm is supported. Node 24 and PHP 8.3 are present. Laravel Boost MCP and Gemini CLI were unavailable during restoration, so source inspection and artisan were used. Dokploy MCP was inspected for deployment, but its available projects were on other deployment environments; native origin hosting preserves this host's existing DNS.

The automated suite covers guest ownership, persistence, duplicate requests, validation, expiry, pruning, no external HTTP calls, separate rate limits and legacy assessment/timer contracts. Browser QA exercises the complete flow, both themes, keyboard skip navigation, responsive widths and the real saved review. Lint warnings in legacy code remain; see the task report for measured counts.

Do not run a frontend build while collecting browser evidence. Do not cache production configuration into the shared development checkout: the service receives a separate environment through systemd. Restart only osce-php after a verified application update.

## Clinical issues requiring owner/examiner review

Some legacy stations include inconsistent scripted behavior (for example speech in an unconscious CPR case). The legacy onboarding practice-chat scenario is generic and can differ from the selected case. These are not exposed by the public guest station. Medical content and scoring were preserved for examiner review instead of being silently corrected.
