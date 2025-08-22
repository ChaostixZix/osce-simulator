Diagnosis: Fix SOAP modal (Dialog a11y/centering), Ziggy route mismatch, and WYSIWYG/timeline not rendering — Laravel + Vue 3 (Inertia) with reka-ui dialogs and Ziggy

Purpose

- Ensure the SOAP-related modal(s) behave correctly and accessibly, resolve the missing Ziggy route error, and restore the Tiptap WYSIWYG editor and SOAP Timeline within the modal.
- Eliminate the console warning: “Missing `Description` or `aria-describedby="undefined"` for DialogContent.”
- Resolve: “Ziggy error: route 'api.soap.patient' is not in the route list.” and align with existing Laravel routes or add API endpoints where needed.

Scope

- Dialog a11y: Provide a valid `DialogDescription` or a safe `aria-describedby` handling for `DialogContent` to prevent undefined ARIA attributes.
- Dialog centering: Ensure the modal popup (notably the Add Patient and SOAP-related popups) is centered consistently across pages and not affected by parent transforms.
- Ziggy routes: Replace or add the proper route for fetching patient/SOAP data used by the modal (either reuse existing `web` routes or add an `api` route; avoid naming drift like `api.soap.patient` unless defined in Laravel).
- SOAP modal content: Make the SOAP modal render the Tiptap WYSIWYG editor component and the past SOAP Timeline reliably on open. Ensure lazy-loading does not break initialization.

Code References (current repo)

- Dialog components (reka-ui wrappers):
  - `webapp/resources/js/components/ui/dialog/DialogContent.vue`
  - `webapp/resources/js/components/ui/dialog/DialogScrollContent.vue`
  - `webapp/resources/js/components/ui/sheet/SheetContent.vue`
- SOAP pages and editor:
  - `webapp/resources/js/pages/Soap/Board.vue` (Board UI)
  - `webapp/resources/js/pages/Soap/Page.vue` (Full SOAP page; reference for data flow)
  - `webapp/resources/js/components/SoapNovelEditor.vue` and `SoapNovelEditorClean.vue` (Tiptap)
- Ziggy setup:
  - `webapp/resources/js/app.ts`, `webapp/resources/js/ssr.ts`
- Routes (existing):
  - `webapp/routes/web.php` contains: `soap.board`, `soap.page`, `soap.store`, `soap.update`, `soap.finalize`, `soap.destroy`, `soap.restore`, `soap.attach`, `soap.upload-image`, `soap.comments.index`, `soap.comments.store`
  - No `api.soap.patient` currently defined.

Constraints

- Keep the SOAP module isolated; do not refactor unrelated modules.
- Preserve existing route names and semantics where possible; if introducing an API endpoint, name it consistently (e.g., `api.patients.show` or `api.soap.patients.show`) and document it.
- Maintain Laravel + Inertia conventions: route helpers via Ziggy, server-side controllers return Inertia for pages or JSON for API.
- Do not add heavy dependencies; use existing reka-ui dialog primitives and Tiptap components already in the repo.

Error Handling

- Dialog a11y: If `DialogDescription` is not provided, do not set `aria-describedby` or inject a hidden description fallback with a stable id. Never render `aria-describedby="undefined"`.
- Ziggy: If a modal requires data fetch, gracefully handle 404/route missing by showing a friendly toast/error and keep the modal open. Ensure route existence at build-time (Ziggy list) and align names.
- WYSIWYG/timeline: If lazy-loaded content fails to fetch, show a retry affordance inside the modal section.

Acceptance Criteria

1) No console warning: “Missing `Description` or `aria-describedby="undefined"` for DialogContent.”
2) Add Patient modal (if used) and SOAP modal(s) render centered in the viewport regardless of surrounding layout wrappers.
3) Clicking the SOAP modal trigger loads and shows:
   - Tiptap WYSIWYG editor (using `SoapNovelEditor*` component) with initial content or empty state.
   - Past SOAP Timeline list (newest first) with relative timestamps as per repo conventions.
4) Ziggy route error is gone; the code uses valid named routes that exist (either reusing `soap.page`/`soap.*` or new `api` route added and registered).
5) If an API endpoint is introduced, it returns patient + relevant SOAP data in JSON (author, state, created_at); respects soft-delete visibility for non-admins.
6) Modal open/close cycles consistently re-initialize the editor without double-mounting or losing content.
7) All changes pass `npm run lint` and typical build; no regressions on the full SOAP page (`/soap/patients/{id}`).

Out of Scope

- Redesigning the SOAP pages or board layout beyond what is needed to fix the modal.
- Changing core Tiptap configuration or replacing the editor.
- Adding new search, export, or notification features.

Objective Actions (do exactly this)

1) Dialog a11y & centering
   - Update `DialogContent.vue` (and related wrappers) to:
     a) Strip falsy `aria-describedby` from forwarded props; only pass it if defined.
     b) Optionally support an internal `<slot name="description">` to render a hidden description with a generated id and map it to `aria-describedby` when no explicit description is provided.
     c) Verify centering uses a fixed-position overlay and translates `(top,left)=50%` with negative transform, not affected by parent transforms. If any parent layout applies transforms affecting positioning, wrap with a full-screen fixed container using `inset-0 flex items-center justify-center`.

2) Route alignment (Ziggy)
   - Locate all references to `route('api.soap.patient')` or similar and either:
     a) Replace with the correct existing route(s), e.g. `route('soap.page', patientId)` if you are navigating, or a new JSON endpoint if you are fetching.
     b) If a JSON fetch is required, add an `api` route in `webapp/routes/api.php`, e.g. `Route::get('soap/patients/{patient}', [SoapPageController::class, 'showJson'])->name('api.soap.patients.show');` that returns patient + latest SOAP notes JSON (reusing query from `SoapPageController@show`).
   - Ensure Ziggy is configured to expose the needed route(s) to the client (normally web routes). For `api` routes, either move to web or enable Ziggy for API if desired; otherwise, use absolute `/api/...` fetch without Ziggy.

3) SOAP modal content
   - Ensure the modal component that opens SOAP (from dashboard/board) imports and renders one of `SoapNovelEditor.vue` or `SoapNovelEditorClean.vue`.
   - Initialize the editor only when the modal is open to avoid SSR/hydration issues; on open, feed initial value via `toTiptapJSON` or empty doc.
   - Render the Timeline below the editor using the same shape as `Soap/Page.vue` (newest first); lazy-load via axios when the modal opens, then cache per patient while open.
   - Verify comment fetching/creation routes (`soap.comments.*`) still function when used inside the modal, or scope them to the note id shown.

4) Visual + UX polish
   - Modal presents a visible title and optional description. Keyboard focus is trapped; Esc closes; close button is visible with sr-only label.
   - Use relative time helpers matching repo conventions for badges/timeline items.
   - Make sure the Add Patient trigger either links to the page (current) or, if a modal is used elsewhere, adopts the same centering and a11y fixes.

5) Documentation
   - Document any new API route name and payload shape in the implementation report.
   - Note which components were updated and how the centering/a11y was resolved.

Code Pointers & Examples

- Centered dialog container approach:
  - Either keep the existing fixed + translate-50% strategy, or use an overlay with `class="fixed inset-0 z-50 grid place-items-center"` and the dialog panel centered by the grid.
- Safe ARIA handling:
  - Only attach `aria-describedby` when a description id is present. Provide a `<slot name="description" />` to let callers supply content cleanly.
- Route usage guidance:
  - For navigation use `route('soap.page', id)` with Inertia router.
  - For data fetching prefer a JSON endpoint with axios and avoid Ziggy for API unless routes are exposed.

Shared Slug

- fix-soap-modal-ziggy-tiptap

