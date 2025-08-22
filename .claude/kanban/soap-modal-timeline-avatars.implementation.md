# Implementation Report — SOAP Modal Timeline + Avatars

Shared slug: soap-modal-timeline-avatars

References:
- Prompt: .claude/kanban/soap-modal-timeline-avatars.prompt.md
- Tests: .claude/kanban/soap-modal-timeline-avatars.tests.md

---

## Summary

Convert patient creation and SOAP timeline input to modals, render the SOAP timeline within a modal, remove the “Open SOAP” button on the Patient Board in favor of a clickable patient name, and add placeholder letter avatars for patients (board) and authors (timeline entries). Initial is derived from the name’s first letter, uppercased.

---

## Changes (to be filled as implemented)

| Path | Change | Rationale | Status |
|---|---|---|---|
| resources/js/Components/Modal/AppModal.vue | New component: accessible modal with focus trap | Reusable modal across Patient and SOAP flows | Pending |
| resources/js/Components/Avatar/InitialAvatar.vue | New component: renders initial-based avatar | Simple, consistent placeholder avatars | Pending |
| resources/js/Pages/SOAP/Board.vue | Replace “Open SOAP” with clickable name; add avatar; open SOAP modal | Cleaner UX; matches requirements | Pending |
| resources/js/Pages/SOAP/Page.vue (or equivalent) | Mount SOAP modal context; render timeline inside modal | Keep user on board; faster workflows | Pending |
| Controllers (Patient/SOAP) | Ensure partial reload endpoints for timeline | Efficient modal updates | Pending |
| routes/web.php | Optional: JSON pagination for timeline | Smooth infinite scroll in modal | Pending |
| utils/date/relative.ts (or similar) | Add tiny relative time helper | Avoid extra libs | Pending |

---

## Migration/DB

No schema changes required. All avatar visuals are derived in the client.

---

## Notes

- Respect Asia/Jakarta timezone and 24h formatting.
- Preserve existing validations and authorization checks.
- Avoid full-page reloads; use Inertia partial reloads.

