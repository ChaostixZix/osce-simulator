# TODO — OSCE Clinical Workflow Parity (Legacy Vue → React + Inertia)

Context: The legacy Vue implementation includes rich OSCE features (AI patient chat, physical exam, test ordering, rationalization workflow, and results/assessment views). The current React pages are minimal and do not yet wire all available backend endpoints. This TODO outlines what to implement to reach functional parity using React + Inertia per Repository Guidelines.

## Backend — Routes & API Wiring

- Add missing Rationalization routes bound to `RationalizationController` with Ziggy names expected by legacy Vue components:
  - `POST /rationalization/cards/{card}/answer` → `RationalizationController@answerCard` → name: `rationalization.answer-card`
  - `POST /rationalization/{rationalization}/diagnoses` → `RationalizationController@submitDiagnoses` → name: `rationalization.submit-diagnoses`
  - `POST /rationalization/{rationalization}/care-plan` → `RationalizationController@submitCarePlan` → name: `rationalization.submit-care-plan`
  - `GET /rationalization/{rationalization}/progress` → `RationalizationController@progress` → name: `rationalization.progress`
  - `POST /rationalization/{rationalization}/complete` → `RationalizationController@complete` → name: `rationalization.complete`

- Keep existing OSCE endpoints; add JSON variants where current controllers redirect:
  - Physical exam: add `POST /api/osce/examinations` (JSON) mirroring `performExamination` to avoid redirects from React.
  - Optional: add `POST /api/osce/procedures` (JSON) mirroring `orderProcedure` for consistency.

- Confirm chat endpoints used by React exist and return JSON:
  - `POST osce.chat.message` (OK), `GET osce.chat.history` (OK), `POST osce.chat.start` (OK).

## Frontend — React Pages & Components

- OsceChat.jsx — extend to full clinical console:
  - Add left/side panels: Case Overview (scenario, objectives, vitals) and session widgets (timer, budget spent, ordered tests, physical exam results).
  - Integrate “Order Tests” flow (port from `OsceTestOrderModal.vue`) using `/api/medical-tests/search` and `POST /api/osce/order-tests` with validation (min 20 chars clinicalReasoning; priority required).
  - Add Physical Exam selector (categories/types from `examCatalog`) to `POST /api/osce/examinations` and show results list (parity with `OscePhysicalExamResults.vue`).
  - Respect time gating via `GET /api/osce/sessions/{session}/timer` and disable actions when inactive/expired.

- Rationalization (new React page) — replace legacy Vue `Pages/Rationalization/Show.vue`:
  - Build `pages/Rationalization.jsx` that renders:
    - Anamnesis cards list (Asked, Negative Anamnesis, Investigations) — port UI/logic from `RationalizationCard.vue` and call new rationalization endpoints.
    - Diagnosis form (primary + differentials) — port from `DiagnosisModal.vue` with client-side validation (50/30 chars), auto-draft to `localStorage`.
    - Structured Care Plan editor — port from `CarePlanEditor.vue` using a React rich text editor (e.g., tiptap-react or a minimal textarea first), autosave every 10s, blur-trigger save.
    - Progress sidebar + “Complete & Unlock Results” button gated by `progress.can_unlock` (poll `rationalization.progress`).

- Results/Assessment (upgrade existing React):
  - `OsceResult.jsx` — extend with sections mirrored from Vue components:
    - Performance Overview (overall score, clinical reasoning %, total test cost, reassess action → `osce.assess.trigger`).
    - Clinical Reasoning summary block with citations (basic rendering, link to details).
    - Detailed assessment table (criteria/areas with scores, justifications, citations) using the new orchestrated `AiAssessmentRun` shape.
  - Add “Check Status” polling UI to `GET osce.status` and show progress by areas with badges.

## Data/Model Assumptions (already present)

- Models exist and are used by controllers/services:
  - `OsceSession`, `OsceChatMessage`, `SessionOrderedTest`, `SessionExamination`
  - Rationalization: `OsceSessionRationalization`, `AnamnesisRationalizationCard`, `OsceDiagnosisEntry`, `RationalizationEvaluation`
  - Assessment runs: `AiAssessmentRun`, `AiAssessmentAreaResult`

## UX/Behavioral Parity (key points)

- Chat: Enter sends, disabled when session inactive; auto-scroll; initial system message on first start.
- Test orders: enforce reasoning length and priority; calculate total cost and show max turnaround; prevent duplicates; reflect `no_data` for unavailable tests.
- Physical exam: multi-select categories/types; ignore already-performed exams; append findings with timestamps.
- Rationalization: cards require minimum lengths; “forgot” toggle for negative anamnesis; server-evaluated progress; only then enable completion.
- Results gating: disallow results view until rationalization complete; reassess button triggers orchestrator; status polling.

## Inertia Rules (React)

- Use `@inertiajs/react` for navigations/mutations; use `fetch` only for pure JSON APIs where appropriate.
- Prefer `router.post/put` for non-JSON endpoints; keep CSRF implicit via Inertia when possible.

## Validation & Tests

- Keep existing Vue unit tests for SessionTimer as reference; add React tests as needed (optional at this stage).
- Manually verify: chat message flow, test ordering validation, physical exam recording, rationalization completion, results assessment and status polling.

## Open Questions

- Pick React editor for Care Plan (tiptap-react vs lightweight textarea). Start with textarea MVP.
- Decide whether to expose JSON APIs for procedures; currently legacy endpoint redirects.

