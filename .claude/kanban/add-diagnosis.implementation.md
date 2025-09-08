Title: Add mandatory diagnosis, differentials (rows), and plan at session end

Goal
- Require users to input primary diagnosis, multiple differential diagnoses (addable rows), and a management plan when finalizing an OSCE session.

Scope
- Frontend only for finalize flow. Keep backend finalize endpoint unchanged by joining multiple differential rows into a readable bullet/numbered string.
- Do not alter database schema.

Changes
- Updated `webapp/resources/js/components/react/FinalizeSessionModal.jsx`:
  - Replaced single `differentialDiagnosis` textarea with dynamic rows (`differentials: string[]`).
  - Added add/remove row controls.
  - On submit, combines non-empty rows into a numbered multiline string and posts as `differential_diagnosis`.
  - Validation: requires primary diagnosis >= 10 chars, at least one differential row (combined len >= 10), and plan >= 10 chars.

Validation Steps
- Navigate to Results page for a completed (but not finalized) session; finalize modal should auto-open.
- Try submitting with empty fields: submit disabled and/or server returns validation errors.
- Add multiple differential rows and submit; backend should accept and store combined text.
- After success, the Results page should display:
  - Primary diagnosis text.
  - Differential diagnosis as a numbered multiline list (thanks to `whitespace-pre-wrap`).
  - Management plan text.

Notes
- Backend remains compatible: controller still expects a single `differential_diagnosis` string; we join rows client-side.
- No migrations were added; we reuse existing `osce_sessions` fields (`diagnosis`, `differential_diagnosis`, `plan`).
- If later we need structured storage, we can add an array/JSON column or reuse the Rationalization diagnosis entries, but it’s out of this scope.
