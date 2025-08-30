Feature slug: end-osce-require-diagnosis-plan

Test Plan

Backend (Pest/PHPUnit)

1) Finalize success
- Given a draft session owned by the user, POST `/api/osce/sessions/{id}/finalize` with all fields.
- Expect 302 redirect, DB has `state=finalized`, `finalized_at` not null, and saved `diagnosis`, `differential_diagnosis`, `plan`.

2) Validation failures (422)
- Missing `diagnosis` → per‑field error; no DB changes.
- Missing `differential_diagnosis` → per‑field error; no DB changes.
- Missing `plan` → per‑field error; no DB changes.

3) Unauthorized (403)
- User without permission tries to finalize another user’s draft session.

4) Conflict (409)
- Finalizing an already finalized session returns 409 and does not mutate data.

Frontend (manual/automated)

5) Modal UX
- “End OSCE” opens modal with three required fields; submit disabled during processing; errors shown inline; modal stays open on error.

6) Success flow
- With valid data, modal closes on success, page shows finalized state; edit controls disabled for non‑admin.

Debugging Summary

- Validate route name matches frontend `route('osce.sessions.finalize', id)`.
- Confirm FormRequest binding in controller method signature.
- Ensure policy registered in `AuthServiceProvider` and gate checks pass.

