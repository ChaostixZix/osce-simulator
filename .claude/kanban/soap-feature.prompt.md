
# SOAP Feature Implementation Prompt

## 1. Backend

### Migration
Create a migration for `soap_notes`:
- `osce_case_id` (foreign key to `osce_cases`)
- `author_id` (foreign key to `users`)
- `subjective`, `objective`, `assessment`, `plan` (text fields)
- `state` (enum: `draft`, `finalized`, default `draft`)
- Timestamps and soft deletes.

### Model
Create a `SoapNote` model:
- `fillable` properties for the fields above.
- Relationships: `osceCase()` and `author()`.

### Controller
Create a `SoapController`:
- `store`: to create/update a SOAP note (autosave).
- `finalize`: to change the state to `finalized`.
- `index`: to list all SOAP notes for an `OsceCase`.

### Routes
In `webapp/routes/web.php`, add routes for the `SoapController` methods, nested under `osce-cases`.

## 2. Frontend

### Vue Components
- Create a `SoapModal.vue` component to display the SOAP notes and form.
- The form should autosave on blur and every 10 seconds.
- Implement a timeline to display previous notes.
- Update the Kanban card component to show the latest SOAP note timestamp and count. The Kanban card is likely in a file like `OsceCaseCard.vue` or similar, which will need to be located.


