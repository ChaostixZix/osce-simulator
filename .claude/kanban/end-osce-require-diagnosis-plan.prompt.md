# End OSCE Require Diagnosis & Plan - Implementation Analysis & Plan

## Reuse-First Analysis Results

### ✅ Existing Infrastructure Found
- **OsceSession Model**: Located at `webapp/app/Models/OsceSession.php:21-40`
- **Complete Session Endpoint**: `POST /api/osce/sessions/{session}/complete` (`webapp/app/Http/Controllers/OsceController.php:254-267`)
- **Current Status Flow**: Session goes `in_progress` → `completed` via `markAsCompleted()` method
- **Database Schema**: Has `status`, `completed_at` fields, but missing diagnosis fields

### ❌ Missing Components
- **No finalize route** - current `/complete` only sets status to completed
- **No FormRequest classes** - validation done inline in controllers
- **No Policies directory** - no authorization policies exist yet
- **Missing DB columns**: `diagnosis`, `differential_diagnosis`, `plan`, `finalized_at`

### 🔄 Existing Related Features
- **Rationalization flow** exists with diagnosis collection but separate from session completion
- **Diagnosis models** exist (`OsceDiagnosisEntry`, `OsceSessionRationalization`) but not integrated into session completion
- **Gaming design system** in place with cyber-border components for UI consistency

## Implementation Plan

### 1. Database Migration
**Priority**: 🔴 CRITICAL
- Add columns to `osce_sessions`: `diagnosis TEXT`, `differential_diagnosis TEXT`, `plan TEXT`, `finalized_at TIMESTAMP`
- Extend `fillable` array in `OsceSession.php:21-40`
- New migration: `add_finalize_fields_to_osce_sessions_table.php`

### 2. Endpoint Strategy - Extend Existing
**Reuse Pattern**: Extend current `completeSession` method rather than create parallel endpoint
- **Route**: Keep `POST /api/osce/sessions/{session}/complete` but add finalize logic
- **Validation**: Add inline validation for diagnosis fields (no FormRequest needed to match patterns)
- **Authorization**: Add simple ownership check (matches existing pattern in `OsceController.php:256-258`)

### 3. Model Updates
- **OsceSession**: Add new fields to fillable, add finalize state checks
- **Methods to add**:
  - `canFinalize()`: check if session is completed and user is owner/admin
  - `isFinalized()`: check if finalized_at is set
  - `finalize($diagnosis, $differential, $plan)`: set fields and finalized_at

### 4. Frontend Implementation
**Gaming Design System Compliance**:
- **Modal component**: Use `cyber-border` styling with emerald color scheme
- **Form validation**: Inline error display with gaming aesthetics
- **Required fields**: All three fields must be filled before submission
- **Error handling**: 422 validation errors shown per field
- **Success state**: Close modal, update session state, disable further edits for non-admins

### 5. Authorization Rules
- **Owner**: Can finalize their own session when status === 'completed' 
- **Admin**: Can always finalize (admin override capability)
- **Prevent double finalize**: 409 error if already finalized

## Acceptance Criteria Implementation

### Backend Validation Rules
```php
$request->validate([
    'diagnosis' => 'required|string|min:10',
    'differential_diagnosis' => 'required|string|min:10', 
    'plan' => 'required|string|min:10'
]);
```

### Frontend UX Requirements
- Inertia modal with cyber-border styling
- Disabled submit during processing
- 422 errors displayed per field with gaming aesthetics
- Close only on success
- Reflect finalized state in UI
- Lock edits for non-admins with admin override shown

### API Responses
- **Success**: 200 with updated session data
- **Validation Error**: 422 with field-specific errors
- **Double Finalize**: 409 conflict error
- **Unauthorized**: 403 with clear message

### Timezone Handling
- All timestamps in `Asia/Jakarta` timezone as specified
- Use Laravel's timezone handling for consistent display

## File Changes Required

1. **Migration**: `webapp/database/migrations/YYYY_MM_DD_add_finalize_fields_to_osce_sessions_table.php`
2. **Model**: `webapp/app/Models/OsceSession.php` (extend fillable + add methods)
3. **Controller**: `webapp/app/Http/Controllers/OsceController.php` (extend completeSession)
4. **Frontend Modal**: New React component with gaming design system
5. **Integration**: Add modal trigger to existing OSCE interface

## Risk Mitigation
- **Backwards compatibility**: New fields nullable, existing complete flow unchanged
- **Data integrity**: Transaction wrapping for finalize operation
- **Authorization**: Simple ownership + admin checks following existing patterns
- **UI consistency**: Strict adherence to gaming design system requirements