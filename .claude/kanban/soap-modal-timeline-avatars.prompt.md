# Feature Prompt — SOAP Modal Timeline + Avatars (Laravel + Vue 3 + Inertia)

Shared slug: soap-modal-timeline-avatars

Cross-links:
- Implementation report: .claude/kanban/soap-modal-timeline-avatars.implementation.md
- Tests plan: .claude/kanban/soap-modal-timeline-avatars.tests.md

---

## Purpose

Improve the SOAP UX by moving “Add Patient” and “SOAP Timeline input” into accessible, Inertia-friendly modals and showing the SOAP timeline itself within a modal. Simplify the Patient Board by removing the “Open SOAP” button: make the patient’s name clickable and show a placeholder profile picture next to names and timeline entries. Avatars are generated from the first letter of the related name (e.g., name “Bintang” → avatar shows the letter “B”). Timeline avatars are based on the author who created each SOAP entry.

Business value: fewer page transitions, faster data entry, clearer visual identity with avatars, and a cleaner patient board.

---

## Scope

In scope (frontend + minor backend wiring as needed):
- Convert “Add Patient” to open in a modal window (client-side Inertia modal) from the Patient Board.
- Convert “Add SOAP Timeline” composer/input to open in a modal on the Patient Page (or inline from Board if present), and display the SOAP timeline inside a modal.
- Replace the Patient Board’s “Open SOAP” button with a clickable patient name that opens the SOAP modal/page context; show a placeholder avatar next to each patient name.
- Show a placeholder avatar next to each timeline entry derived from the entry author’s display name.
- Keep existing validations and persistence; only change interaction and UI surfaces.

Out of scope (for now):
- Real image uploads or profile image storage.
- Changing authorization/policies beyond existing rules.
- Changing core SOAP domain fields or introducing notifications/export.

---

## Inputs / Outputs / Side Effects

- Inputs:
  - Add Patient modal: existing patient form fields (minimally `name`, reuse what Patient create currently validates).
  - Add SOAP Timeline modal: existing SOAP timeline form fields (e.g., summary/content fields already used for creating a SOAP note).
- Outputs:
  - On success, the board/page revalidates and reflects the new patient or timeline entry. The modal closes and shows a toast/flash.
- Side effects:
  - None beyond existing create/update actions and flash messages.

---

## Code References (expected paths)

- Routes: webapp/routes/web.php (web routes for Patient board/page and SOAP actions).
- Controllers: webapp/app/Http/Controllers/** (Patient and SOAP-related controllers, e.g., PatientController, SoapNoteController).
- Models: webapp/app/Models/** (e.g., Patient.php, SoapNote.php or similar timeline model).
- Vue Pages:
  - webapp/resources/js/Pages/SOAP/Board.vue (patient listing board; remove button, make names clickable, add avatars).
  - webapp/resources/js/Pages/SOAP/Page.vue (if a dedicated page exists; mount/drive SOAP timeline modal).
- Vue Components:
  - webapp/resources/js/Components/Modal/*.vue (create `AppModal.vue` if a modal does not yet exist).
  - webapp/resources/js/Components/Avatar/*.vue (create `InitialAvatar.vue` to render a circular placeholder with name initial).

If filenames differ, search for existing patient list/timeline pages under `webapp/resources/js/Pages` and adjust accordingly.

---

## Constraints

- Stack: Laravel, Vue 3, Inertia 2.x, dev DB SQLite. App timezone: Asia/Jakarta, 24h, DD/MM/YYYY.
- Do not introduce external UI libraries; implement a small, accessible modal (focus trap, ESC to close, overlay click to close, trap tab order).
- Keep all actions using Inertia requests (POST/PUT/DELETE) or GET with partial reloads.
- No new DB fields required for avatars; generate letter avatars from display names.

---

## Error Handling

- Modal forms: show validation errors inline; do not close the modal on validation failure.
- On network error: show a non-blocking error toast/alert in the modal; allow retry; keep unsaved inputs.
- If an author name is missing/blank, fallback to `?` as the avatar letter.

---

## Acceptance Criteria

1. Patient Board shows each patient with a small circular placeholder avatar and a clickable name. No separate “Open SOAP” button exists.
2. Clicking a patient name opens the SOAP context via an Inertia modal. If a dedicated page exists, the modal is layered on top and can be closed to return to the board without a full page reload.
3. Add Patient opens in a modal with the existing create patient fields and validations. Submitting successfully closes the modal and refreshes the board list.
4. Add SOAP Timeline opens in a modal with the existing timeline fields and validations. Submitting successfully closes the modal and refreshes the timeline content.
5. The SOAP timeline itself can be viewed within a modal. Newest entries appear first, and the modal supports scrolling without shifting the page behind it.
6. Each timeline entry shows a left-aligned placeholder avatar for the author. The avatar displays the uppercase initial of the author’s name (e.g., “Bintang” → “B”).
7. Avatar fallback: if the author name is missing, show `?`. If the author name contains leading whitespace, trim it before extracting the initial.
8. Timeline entries include relative timestamps (e.g., “2h ago”) consistent with Asia/Jakarta and 24h formatting when absolute.
9. Modal UX: focus moves into the modal on open, ESC and overlay click close the modal, tabbing is trapped within.
10. Inertia partial reload preserves scroll state behind the modal and does not cause layout jumps.
11. No regression to existing permission checks for creating/updating patients or SOAP entries.
12. The UI matches existing styling conventions (Tailwind classes tidy, imports ordered) and passes `npm run lint`/`format:check`.

---

## Out of Scope

- Real profile photos or uploads; keep to placeholder letter avatars.
- Changing SOAP data model or adding export/print.
- Notifications, email, or external integrations.

---

## Objective Actions (do exactly this)

1) Introduce reusable modal component
   - Create `resources/js/Components/Modal/AppModal.vue` with props: `modelValue` (v-model), `title`, `width` (e.g., `md|lg|xl`), and slots for content/actions.
   - Implement focus trap, ESC to close, overlay click to close, and proper ARIA attributes.

2) Introduce initial avatar component
   - Create `resources/js/Components/Avatar/InitialAvatar.vue` with props: `name` (string), `size` (`sm|md|lg`), and optional `bgClass`.
   - Derive initial: first letter of `name.trim()` uppercased, fallback to `?`.

3) Patient Board changes
   - Update `resources/js/Pages/SOAP/Board.vue`:
     - Remove “Open SOAP” button.
     - Render `InitialAvatar` next to each patient name (e.g., 24px circle with the letter).
     - Make patient name a clickable target that opens the SOAP modal context for that patient.
     - Preserve existing filters/search/sort/pagination.

4) SOAP modal context
   - Add a modal flow that loads the SOAP context for a selected patient:
     - On click, open an `AppModal` that renders the SOAP detail skeleton and loads initial data via Inertia GET (partial props `only: ['soap']`).
     - Include “Add Timeline” button that opens the composer modal.
     - Support closing the modal without leaving the board.

5) Add Patient modal
   - Add a button “Add Patient” on the board opening `AppModal`.
   - Inside the modal, mount the existing patient create form using an Inertia form.
   - On success, close modal and refresh board list (keep query string; `preserveScroll: true`).

6) Add SOAP Timeline modal
   - On SOAP modal, provide “Add SOAP Timeline” button that opens a composer modal.
   - Fields mirror existing timeline create; use Inertia form post.
   - On success, close the composer modal and partially reload timeline (`only: ['timeline']`).

7) Timeline in modal
   - Render timeline items inside the SOAP modal.
   - Each item shows `InitialAvatar` computed from the author name and a relative timestamp.
   - Maintain newest-first order; paginate or infinite-scroll inside the modal if the list is long.

8) Relative time helper
   - Create or reuse a tiny util for relative timestamps; no external date libs.
   - Ensure formatting aligns with Asia/Jakarta and 24h when absolute.

9) Controller/route adjustments (minimal)
   - Keep existing routes; ensure endpoints support partial reloads for timeline.
   - If needed, add a lightweight JSON route to fetch timeline pages for the modal.

10) Styling and linting
   - Conform to project’s Tailwind and ESLint/Prettier rules.
   - Keep imports ordered and classes tidy; run `npm run lint` and `npm run format:check`.

---

## Constraints to Follow (Performance/Security/Conventions)

- Do not block the UI during modal loads; show a spinner/skeleton state.
- Ensure no PII is exposed beyond existing props; trim and sanitize user-provided display names for avatar initials.
- Follow Inertia conventions: `preserveState`, `preserveScroll`, `only` for partial reload; avoid full page reloads.

---

## Validation & Logging

- Validation errors render within the modal form; fields show messages inline.
- Non-2xx responses show a toast/alert with a short message; keep the modal open.
- Console noise minimized; no sensitive data logged.

---

## Notes on Avatar Initials

- Algorithm: `initial = (name || '').trim()[0]?.toUpperCase() || '?'`.
- Visual: circular container, centered letter, neutral background (e.g., gray-200) with dark text; sizes map to Tailwind classes.
- Examples:
  - name = "Bintang" → avatar shows "B".
  - name = "  anna" → avatar shows "A".
  - name = "" → avatar shows "?".

