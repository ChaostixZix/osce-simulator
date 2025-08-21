# SOAP Feature Testing

## Test Plan

1.  **Migration and Model:** Verify the `soap_notes` table is created correctly in the database.
2.  **Create SOAP Note:** Open the SOAP modal and create a new note. Check for autosave functionality.
3.  **Update SOAP Note:** Edit an existing draft and verify the changes are saved.
4.  **Finalize SOAP Note:** Finalize a draft and ensure it becomes read-only for non-admins.
5.  **Kanban Card Display:** Confirm the SOAP note count and latest timestamp are displayed correctly on the OSCE case card.
