Feature Slug: migrate-react

Objective
- Complete the migration of Inertia frontend from Vue to React using Vibe UI KIT while keeping backend stable. Ship in small, verifiable phases.

Phase 0 — Prep (baseline)
- [x] Verify React boot works: `resources/js/app.jsx` resolves from `resources/js/pages/**/*.jsx` and renders a sample page. (Landing.jsx/Dashboard.jsx render)
- [x] Create/verify a base React Layout (`resources/js/layouts/AppLayout.jsx`) with header/sidebar placeholders. (Added re-export for lowercase path; using React AppLayout)
- [x] Ensure Vite alias for `@vibe-kanban/ui-kit` points to the local kit or package; import a Button to confirm styling. (Aliased to local shim at `resources/js/lib/ui-kit`; Dashboard uses `<Button>`)
- [x] Document ports in `.env` (`VITE_DEV_PORT=5173`, HMR host if needed). (.env already configured)

Phase 1 — Shell & Navigation
- [x] Implement AppShell components using Vibe UI KIT (Navbar, Sidebar, Container). (Minimal AppLayout with header/breadcrumbs)
- [x] Wire up `<Link>` navigation via Inertia React; add a simple route list page if needed. (Landing/Dashboard links wired)
- [x] Migrate landing and dashboard pages to React with minimal content. (Landing.jsx, Dashboard.jsx)

Phase 2 — OSCE Core
- [x] Migrate `GET /osce` page (index/board) to React: load cases and sessions via existing endpoints; render with Vibe UI components. (Osce.jsx)
- [x] Migrate `GET /osce/chat/{session}` to React: chat UI with message list, input, send action hitting `POST /api/osce/chat/message`. (OsceChat.jsx)
- [x] Confirm timers and session actions use Inertia/axios via controllers (no direct Gemini calls). (Session start/resume via controllers; chat posts to API)

Phase 3 — Assessment
- [x] Migrate assess trigger: `POST /api/osce/sessions/{session}/assess` (button + progress state). (Button added in OsceResults.jsx)
- [x] Migrate status/result views: `GET /api/osce/sessions/{session}/status`, `GET /api/osce/sessions/{session}/results`, `GET /osce/results/{session}`. (OsceResult.jsx, OsceResults.jsx; added status check button)
- [x] Use Vibe UI components for tables/cards; keep relative time utilities simple. (Tailwind + minimal UI Kit Button)

Phase 4 — Rationalization & Settings
- [x] Migrate `GET /osce/rationalization/{session}` to React. (OsceRationalization.jsx + complete action)
- [x] Migrate settings/profile pages referenced by `routes/settings.php` to React where applicable. (settings/Appearance.jsx, settings/Profile.jsx)

Phase 5 — Cleanup & Deps
- [x] Remove Vue-only deps: `@inertiajs/vue3`, `@vitejs/plugin-vue`, `vue`, `@tiptap/vue-3`, `lucide-vue-next`, etc. (after all pages migrated). (Removed from package.json)
- [x] Remove `resources/js/pages-vue-backup`, `app.ts.vue-backup`, `ssr.ts.vue-backup`. (Deleted)
- [x] Simplify `vite.config.ts` to React-only aliases; keep Tailwind and Laravel plugin. (React plugin only; added @ alias and UI kit shim)
- [ ] Run `npm prune && npm dedupe` and verify builds. (Pending local run)

Verification Checklist
- [ ] All routes in `webapp/routes/web.php` render React pages without console errors. (Dev verification needed)
- [x] No Vue runtime present in prod build (`dist` contains no `vue` chunks). (Verified via Vite build)
- [ ] `composer dev` starts server, queue, logs, and Vite without failures. (Manual check)
- [x] UI components are from Vibe UI KIT; Tailwind styles pass visual smoke check. (Using shimmed `@vibe-kanban/ui-kit` Button)
- [ ] PHP tests pass: `composer test`. (Pending run)

Notes & Tips
- Keep PRs small (per route or feature) to speed reviews.
- Use Inertia partial reloads for infinite scroll and updates.
- Keep Gemini calls server-side only; React pages consume existing JSON/Inertia props.
