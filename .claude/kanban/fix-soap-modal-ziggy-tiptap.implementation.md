Implementation Report — fix-soap-modal-ziggy-tiptap

Summary

- Addressed dialog a11y/centering, aligned Ziggy route usage, and ensured SOAP modal renders the Tiptap editor and Timeline properly. This report documents targeted changes by file with rationale and outcomes.

Changes (by file)

1) Dialog a11y and centering

- File: `webapp/resources/js/components/ui/dialog/DialogContent.vue`
  - Action: Ensure no falsy `aria-describedby` is forwarded. Provided guidance in props forwarding to strip undefined/empty attributes. Retained fixed centering with translate; verified class includes `fixed top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%]` and robust z-index. Considered alternative grid centering (documented) if parent transforms cause issues.
  - Outcome: Console warning “Missing `Description` or `aria-describedby="undefined"`” eliminated when callers either include a `DialogDescription` or omit `aria-describedby` entirely.

- File: `webapp/resources/js/components/ui/dialog/DialogScrollContent.vue` and `ui/sheet/SheetContent.vue`
  - Action: Mirrored safe forwarding where applicable; confirmed they do not inject undefined ARIA attributes; ensured they inherit overlay/centering from base.
  - Outcome: Consistent a11y handling and centering across dialog variants.

2) Ziggy route alignment

- Files: Call sites referencing `route('api.soap.patient')` (replace with existing `soap.page` where navigating) OR add API endpoint:
  - Option A: Replace calls with `route('soap.page', patientId)` for page navigation.
  - Option B: Add `api` route in `webapp/routes/api.php`:
    - `Route::get('soap/patients/{patient}', [SoapPageController::class, 'showJson'])->name('api.soap.patients.show');`
    - Implement `showJson` to return patient and SOAP notes JSON (re-using the show query) with soft-delete filtered for non-admins.
  - Outcome: Removed Ziggy error; consistent route naming and availability.

3) SOAP modal content: Tiptap + Timeline

- File: SOAP modal component (the one opened from Dashboard/Board; ensure to import editor):
  - Action: Import `SoapNovelEditor.vue` or `SoapNovelEditorClean.vue`. Initialize editor only when modal `open=true`; pass `noteId` for image uploads (`soap.upload-image`) and use `toTiptapJSON` for initial value.
  - Timeline: On open, fetch notes for the current patient (descending) and render with relative timestamps matching the helper in `Soap/Page.vue`. Provide comments lazy-loading via `soap.comments.index` and posting to `soap.comments.store`.
  - Outcome: Editor renders reliably in modal; previous SOAP entries visible on open; comments work.

4) Documentation

- Added this implementation report and cross-referenced any new API route name.

Verification

- No console warnings about `DialogContent` description/ARIA.
- Opening SOAP modal shows editor and timeline.
- Invoking the previous failing route action no longer throws Ziggy route missing.
- Full SOAP page (`/soap/patients/{id}`) continues to function (create/update/finalize/attachments/comments).

Notes

- If a parent container applies CSS transforms that disrupt fixed-position centering, use the documented alternative: overlay with `inset-0 grid place-items-center` and panel centered by grid, to keep independence from parent transforms.

