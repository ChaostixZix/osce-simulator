Testing Plan & Results — fix-soap-modal-ziggy-tiptap

Test Matrix

- Dialog a11y
  - Open each dialog usage (including any Add Patient modal if present, and SOAP modal)
  - Expect: No console warning “Missing Description or aria-describedby=undefined”
  - Check: Focus trap works; Esc closes; close button has sr-only “Close”

- Dialog centering
  - Trigger the same modals on Desktop and resize to Mobile widths
  - Expect: Modal content vertically and horizontally centered in viewport; not offset by parent layout

- Ziggy routes
  - Reproduce previously failing action that called `route('api.soap.patient')`
  - Expect: No Ziggy error; if navigation, it goes to SOAP page (`soap.page`); if fetch, API returns JSON (200) with patient + notes

- SOAP modal content (WYSIWYG + Timeline)
  - Open SOAP modal from Dashboard/Board
  - Expect: Tiptap editor visible and interactive; initial value populated (existing draft) or empty doc
  - Expect: Timeline shows newest-first notes with relative time badges
  - Comments: expanding a note loads comments via `soap.comments.index`; posting creates new comment and refreshes list

- Regression checks
  - Full SOAP page (`/soap/patients/{id}`): create draft, autosave/PUT, finalize, add attachments, load/post comments
  - Board page: filters/search/sort still operate; Add Patient link navigates correctly

Manual Test Steps

1) Start app: `cd webapp && composer dev`
2) Login and go to `/dashboard`
3) Trigger the SOAP modal (same path that previously errored)
   - Observe console: no ARIA warning; no Ziggy error
   - Editor and Timeline rendered; type into editor; close and reopen — state initializes correctly
4) If using API route: Inspect Network tab for `/api/soap/patients/{id}` — JSON matches expected shape (notes newest-first)
5) Visit `/soap` → open a patient → verify page behavior unchanged

Results

- [ ] No warnings/errors in console during modal open/interaction
- [ ] Dialog is centered across viewports
- [ ] Ziggy route error resolved
- [ ] Editor and timeline render as expected
- [ ] SOAP page behaviors unchanged

