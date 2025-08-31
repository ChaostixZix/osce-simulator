Title: OSCE Assessment Style Consistency

Goal
- Make OSCE assessment/results UI consistent with dashboard/OSCE pages using the shared AppLayout and “cyber” styling (cyber-border, cyber-button, lowercase headings, muted foregrounds).

Scope
- Frontend style-only adjustments to the OSCE Results page component.
- No backend/route/controller changes; reuse existing data and behavior.

Changes
- Updated styles in `webapp/resources/js/pages/OsceResult.jsx`:
  - Breadcrumbs and `<Head>` title normalized to lowercase.
  - Replaced plain header with dashboard-like header block (gradient lines, lowercase title).
  - Converted plain boxes (`bg-white border rounded-lg`) to `cyber-border bg-card/30` containers with section headers.
  - Standardized typography: lowercase headings, `text-foreground`/`text-muted-foreground`, occasional `font-mono` for meta labels.
  - Restyled reassess button and navigation links to use `cyber-button` with consistent colors and sizes.
  - Harmonized alert/warning boxes to `cyber-border` with translucent colored backgrounds for consistency.

Validation
- Build UI: `cd webapp && npm run dev` (or `npm run build`) and open the OSCE results page (`/osce/results/{session}`) for a session that has completed rationalization.
- Confirm the following are visually consistent with dashboard/OSCE pages:
  - Page header (accent lines, lowercase title).
  - Section containers use cyber-border with subtle backgrounds.
  - Buttons use `cyber-button` and align with palette in dark/light mode.
  - Typography is lowercase with muted foreground for meta texts.
  - Warning/connection messages use consistent bordered panels.

Notes
- Functionality is unchanged (queue indicator, polling, reassess action, links).
- QueueIndicator styling remains as implemented previously; it already reads well within the adjusted layout.
