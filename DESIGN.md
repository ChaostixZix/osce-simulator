# OSCE Simulator

## Purpose and boundaries

A visitor can practise an existing OSCE station without an account or external AI credentials. The public station uses the authored Chest Pain Assessment case, stores a real encounter and returns an evidence review. It does not issue a clinical grade or certify competence.

Clinical case text, scripted replies, checklists, investigation values and scoring definitions are preserved. Missing clinical information is labelled unavailable; it is never invented. Existing account-based tools remain separate and require their configured services.

## Architecture and station flow

Laravel 12 owns routing, validation, ownership and persistence. React 19 pages are delivered through Inertia 2 and Vite. SQLite is the lean local default. PostgreSQL remains supported.

Landing -> briefing -> explicit start -> timed encounter -> summary submission -> persisted evidence review.

Guest ownership uses a random token held in the encrypted server-side session and a SHA-256 hash on the encounter. It is independent of WorkOS and grants no account privileges. Every guest endpoint checks ownership. Only the featured, active case is available through the guest flow.

The timer is derived from the original started_at. Reloads do not restart it. At expiry the conversation becomes read-only, but the learner can still submit the summary. Submission is transactional and idempotent. Reviews are persisted after the summary, never before it.

## Patient layer

LocalPatientService matches common English question intents to authored ai_patient_responses and relevant recorded facts. It returns the reply and a source/topic identifier. Unknown information produces an explicit limitation. No remote provider is constructed on this path.

The public guest experience remains deterministic even if account-based provider credentials are configured. Provider-backed patient chat is an explicit opt-in for account features; adding credentials does not establish clinical validity.

## Assessment layer

EvidenceReviewService reports the recorded transcript, question topics, examination/test actions, submitted summary and the unchanged station checklist. It distinguishes recorded evidence from skills requiring an examiner. It never converts keyword matches into a clinical grade, diagnosis validation or pass/fail decision. Numeric scoring configurations remain unchanged.

## Frontend and visual direction

Reading: a medical-training workspace for students and examiners, in a restrained clinical visual language.
Dials: ENERGY 2 / RHYTHM 2 / MOTION 1.

- Instrument Sans: the existing typeface suits extended reading and compact clinical controls; host it locally to remove a network dependency.
- Warm neutral background and dark ink: readable, calm surfaces for long encounters.
- Deep green action accent: establishes the primary next action without coloring every panel.
- Clinical record motif: section numbers, fine dividers and clearly labelled observations tie the landing page to the encounter.
- Landing: product explanation beside an actual station brief, followed by the review's real capabilities and limits.
- Encounter: briefing and recorded actions support a dominant conversation column; mobile puts the conversation first with collapsible supporting material.
- Review: evidence and original checklist sit together, with the submitted summary and transcript available for inspection.
- Cards separate actual tasks, not decorative feature collections.
- Motion is limited to focus, hover and short state transitions; no looping decoration.

## Tokens and accessibility

Guest styles are scoped to .station-app so legacy pages retain their established layout.
Light: background #f5f5ef, surface #ffffff, ink #202d29, muted #52615a, primary #176448, primary text #ffffff, border #d5ddd5.
Dark: background #101d18, surface #192a22, ink #f1f5ec, muted #bbc9be, primary #9bd8b0, primary text #11271b, border #40584a.
Use CSS custom properties for every guest surface and state.
Typography: body 16px/1.6; page titles fluid; form labels 14px or larger; auxiliary metadata 12-13px. Inputs remain 16px.
Spacing: 4/8/12/16/24/32/48/64px. Radii: 6px controls, 12px task surfaces.
Focus uses a visible 3px outline. Controls have at least 44px touch targets. Layout reflows at content-driven intermediate widths. Both themes, reduced motion and keyboard navigation are acceptance checks.

## Deviations from DESIGN_SYSTEM.md

Keep clean-card/clean-button, theme variables and the spacing hierarchy. Guest cards do not lift on hover because they are reading surfaces, not clickable objects. The landing title is larger than the application's text-2xl title to establish its purpose immediately. Remove cyber decoration, generic marketing claims and unverified testimonials. These changes serve clarity and the real station workflow.

## Verification

Pest covers ownership, persistence, no-provider behavior and lifecycle boundaries. Browser QA completes the actual station at 1440px and 390px, including review reload, and records console, page and network errors. Clinical-content concerns are reported rather than silently corrected.
