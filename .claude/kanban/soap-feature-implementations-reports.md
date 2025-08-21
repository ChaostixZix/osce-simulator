# SOAP Feature Implementation Report

This report summarizes the implementation status of the standalone SOAP module.

| Feature | Status | Notes |
|---|---|---|
| **Backend Setup** | | |
| Timezone Configuration | ✅ Working | `config/app.php` set to `Asia/Jakarta`. |
| Database Migrations | ✅ Working | Tables for `patients`, `soap_notes`, `soap_attachments`, `soap_comments` created. |
| Eloquent Models | ✅ Working | All models and their relationships (`hasMany`, `belongsTo`) are defined. |
| Authorization Policy | ✅ Working | `SoapNotePolicy` is implemented and registered, controlling access for all actions. |
| Routing | ✅ Working | All required routes under `/soap` are defined in `routes/web.php`. |
| Controllers | ✅ Working | All controllers (`SoapBoard`, `SoapPage`, `SoapNote`, `SoapAttachment`, `SoapComment`) are implemented. |
| **Patient Board (`/soap`)** | | |
| Patient List Display | ✅ Working | Displays a paginated list of patients with their ward/room number. |
| Search (by Name) | ✅ Working | Patient search by name using SQL `LIKE` is functional. |
| Filter (by Status) | ✅ Working | Patients can be filtered by `active` or `discharged` status. |
| Sorting | ✅ Working | Sorting by name, admission date, and latest SOAP note is implemented. |
| Pagination | ✅ Working | Patient list is paginated and navigation works. |
| **Patient Page (`/soap/patients/{id}`)** | | |
| SOAP Form | ✅ Working | Form for Subjective, Objective, Assessment, and Plan is displayed. |
| Autosave (on blur + 10s) | ✅ Working | Form autosaves on field blur and every 10 seconds. "Saving..." / "Saved" indicator is present. |
| Note Creation & Update | ✅ Working | The first save creates a new draft note; subsequent saves update the same draft. |
| Finalize Note | ✅ Working | "Finalize" button locks the note from being edited by non-admins. |
| Admin Override | ✅ Working | Admins can edit finalized notes. |
| Timeline View | ✅ Working | Displays SOAP notes in descending order with author, status, and relative time. |
| Infinite Scroll | ✅ Working | More notes are loaded automatically as the user scrolls down the timeline. |
| Attachments | ✅ Working | File uploads (≤5 MB) are working and attachments are displayed as download links. |
| Comments | ✅ Working | Comments are lazy-loaded on expand and new comments can be posted. |
| **Missing Features** | | |
| Add New Patient | ❌ Not implemented | There is currently no UI or backend logic to create new patients. |
| Edit Patient Details | ❌ Not implemented | There is no functionality to edit existing patient information. |
| Restore Soft-Deleted Note| ❌ Not implemented | The backend route exists, but there is no UI button for admins to restore deleted notes. |
