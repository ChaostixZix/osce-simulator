# Tests Plan — SOAP Modal Timeline + Avatars

Shared slug: soap-modal-timeline-avatars

References:
- Prompt: .claude/kanban/soap-modal-timeline-avatars.prompt.md
- Implementation: .claude/kanban/soap-modal-timeline-avatars.implementation.md

---

## Test Matrix

| Area | Scenario | Steps | Expected |
|---|---|---|---|
| Board UI | No “Open SOAP” button | Visit Patient Board | Button absent |
| Board UI | Patient name clickable | Click patient name | SOAP modal opens, board remains behind |
| Board UI | Avatar next to name | Observe list | Circle avatar shows first letter or `?` |
| Add Patient | Open modal | Click “Add Patient” | Modal appears with form, focus on first field |
| Add Patient | Validation | Submit empty | Inline errors, modal remains open |
| Add Patient | Success | Submit valid | Modal closes, board reloads with new patient |
| SOAP Timeline | Open composer modal | In SOAP modal, click “Add SOAP Timeline” | Composer modal opens |
| SOAP Timeline | Validation | Submit empty | Inline errors, modal remains |
| SOAP Timeline | Success | Submit valid | Composer modal closes; timeline refreshes |
| Timeline View | Avatars by author | Observe entries | Each entry shows author initial |
| Timeline View | Relative time | Observe timestamps | Relative time shows correctly (Asia/Jakarta) |
| Accessibility | Focus handling | Open/close modals | Focus trapped inside; returns to trigger on close |
| Accessibility | ESC/overlay | Press ESC/click overlay | Modal closes |

---

## Manual Verification Steps

1. Run webapp locally: `cd webapp && composer install && npm install && composer dev`.
2. Seed or create at least one user and a few patients.
3. Visit the Patient Board and verify UI changes:
   - No “Open SOAP” button; names are clickable with avatars.
4. Click a name to open the SOAP modal:
   - Verify timeline renders in the modal.
   - Add a new SOAP entry via the composer modal.
   - Confirm author initial avatar renders (e.g., user name “Bintang” → “B”).
5. Open “Add Patient” modal, submit invalid/valid inputs, verify expected behavior.

---

## Debugging Notes

- If avatars render incorrect initials, log the computed `name.trim()` and the first character.
- If modal focus trapping fails, check tabindex on sentinels and ensure key handlers are registered at mount.
- If Inertia partial reloads cause layout jumps, ensure `preserveState` and `preserveScroll` are set, and only the needed props are requested via `only`.

