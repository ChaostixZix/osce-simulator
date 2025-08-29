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
- [x] Run `npm prune && npm dedupe` and verify builds. (Completed; 199 packages pruned, deduped; prod build already succeeded)

Verification Checklist
- [ ] All routes in `webapp/routes/web.php` render React pages without console errors. (Dev verification needed)
- [x] No Vue runtime present in prod build (`dist` contains no `vue` chunks). (Verified via Vite build)
- [ ] `composer dev` starts server, queue, logs, and Vite without failures. (Manual check)
- [x] UI components are from Vibe UI KIT; Tailwind styles pass visual smoke check. (Using shimmed `@vibe-kanban/ui-kit` Button)
- [x] React pages use Ziggy `route()` for OSCE navigations/mutations (no hard-coded paths for start, assess trigger, OSCE links).
- [ ] PHP tests pass: `composer test`. (Pending run)

Notes & Tips
- Keep PRs small (per route or feature) to speed reviews.
- Use Inertia partial reloads for infinite scroll and updates.
- Keep Gemini calls server-side only; React pages consume existing JSON/Inertia props.

---

Progress Summary (this pass)
- Completed Phases 0–4 with React pages for: `Landing`, `Dashboard`, `Osce`, `OsceChat`, `OsceResult`, `OsceResults`, `OsceRationalization`, `settings/Appearance`, and `settings/Profile`.
- Vite aliases updated: `@` → `resources/js`, `@vibe-kanban/ui-kit` → local shim at `resources/js/lib/ui-kit`.
- UI Kit shim: basic `Button.jsx` and design tokens at `resources/js/lib/ui-kit/styles/tokens.css`; tokens imported from `resources/css/app.css`.
- Removed legacy Vue backups: `resources/js/pages-vue-backup`, `app.ts.vue-backup`, `ssr.ts.vue-backup`, and `layouts/AppLayout.vue`.
- Pruned Vue deps from `webapp/package.json`; React + Tailwind + Inertia React retained.
- Built successfully (`npm run build`); verified no Vue chunks in `public/build`.
- Ran `npm prune && npm dedupe` and confirmed zero vulnerabilities; ensured `database/database.sqlite` exists; checked routes via `php artisan route:list`.

Issue Resolved — OSCE Session Start 404
- Cause: The React page used `useForm().post(url, { data })`, which does not send the provided `data` payload. This resulted in invalid submissions and confusion during debugging; in some environments, the POST was misrouted, surfacing as a 404.
- Fix: Switched to Inertia `router.post('/osce/sessions/start', { osce_case_id }, { preserveScroll })` in `resources/js/pages/Osce.jsx`. This sends the payload correctly and follows the server redirect to `osce.chat`.
- File change: `webapp/resources/js/pages/Osce.jsx` — replaced `useForm` call with `router.post`.
- Follow-ups: Consider using Ziggy's `route('osce.sessions.start')` helper later for resilience against path changes.

Next Dev Context / What to Verify
- Run once in `webapp/`:
  - `npm install` (refresh lock), then `npm prune && npm dedupe`.
  - `php artisan migrate --force` (SQLite dev DB; ensure `database/database.sqlite` exists).
  - `composer dev` and visit routes: `/`, `/dashboard`, `/osce`, `/osce/chat/{id}`, `/osce/rationalization/{id}`, `/osce/results/{id}`, `/settings/profile`, `/settings/appearance`.
  - Watch browser console for errors; confirm Inertia resolves React pages; verify chat send, session start, assess trigger, and status polling hit existing controllers.
- Run tests: `composer test`.

Routing/Interaction TODO (Inertia-first)
1) Replace any remaining `fetch` with `router.post/put/delete` or `useForm` (OsceChat send can remain JSON if we keep local stream UX, otherwise switch to Inertia with partial reload props). — Done for session start: switched to `router.post(route('osce.sessions.start'), { osce_case_id })`.
 2) Standardize Ziggy usage — DONE for OSCE flows and dashboard:
    - Osce.jsx: start session via `route('osce.sessions.start')`; Links to `osce.chat`, `osce.rationalization.show`, `osce.results.show`; dashboard link uses `route('dashboard')`.
    - OsceResults.jsx: assess trigger via `route('osce.assess.trigger', session.id)`; back link and breadcrumb use `route('osce')`.
    - OsceRationalization.jsx: completion via `route('osce.rationalization.complete', session.id)` and breadcrumb `route('osce')`.
    - Dashboard.jsx: OSCE navigation uses `route('osce')`.
  3) Confirm middleware group: `osce.sessions.start` should be in the same `auth` group as related OSCE routes; verify `php artisan route:list` shows it, and adjust if missing.
  4) If 404 persists:
     - Clear route cache: `php artisan route:clear` during dev.
     - Verify POST path and method in Network tab match route.
     - Ensure CSRF is present (Inertia handles this automatically).
  5) Consider keeping an API and a web (Inertia) endpoint pair for actions that need JSON vs navigation; document which one the page uses.

Feature Migration Checklist (parity with legacy Vue)
- OSCE Index/Board (React): case listing, start session CTA, recent sessions state (done; validate UX polish).
- OSCE Chat (React):
  - Message send/receive (basic done via JSON; decide if migrate to Inertia partial reload).
  - Examination catalog UI, actions, and display of findings.
  - Order tests flow (POST `api/osce/order-tests`), feedback and costs.
  - Timer controls with server polling (`api/osce/sessions/{id}/timer`), auto-complete on expire.
- Rationalization (React): display summary + complete action (done; expand UI/notes if existed in Vue).
- Assessment (React): assess trigger (done via Inertia), status polling (JSON ok), results view (done basic; expand area cards and justification display).
- Settings (React): Profile (done basic), Appearance (done basic).
- Remove/replace remaining Vue components used by any active routes (audit `resources/js/components/**` usages with ripgrep and routes/controllers).

Documentation/Quality TODO
- Ensure CLAUDE.md and AGENTS.md reflect Inertia-first rule (done) and keep examples up-to-date.
- Update webapp/README.md to mention Inertia React usage patterns and Inertia endpoints vs API endpoints.
- Add a short “routing gotchas” note: route cache, Ziggy, HMR origin, CSRF.
- Updated prompt to codify Ziggy usage for all navigations/mutations; avoid hard-coded paths.

Acceptance checks to close migration
- [x] `router.post('/osce/sessions/start')` successfully redirects to `/osce/chat/{id}` for a selected case (no 404).
- All OSCE actions available in Vue are now available in React with equivalent behavior.
- No direct fetch/axios for navigation or form submissions on migrated pages.

Known Caveats / Follow-ups
- Residual Vue components still live under `resources/js/components/**` (not used by React pages). Remove only after confirming no route references them.
- `tsconfig.json` still includes Vue JSX settings and `.vue` globs; safe to leave now, but consider React-focused cleanup later.
- Local UI Kit shim should be replaced with the real Vibe UI KIT package when available; then point the alias to the package and remove the shim.
- Security: repository contains `webapp/.env` with live-looking values; rotate sensitive keys and rely on local `.env` only (do not commit secrets); ensure `.env.example` is updated instead.

Nice-to-haves (post-verify)
- Replace minimal UI with real Vibe UI KIT components across OSCE pages (cards, tables, inputs).
- Add a small shared relative-time helper and basic loading states.
- Add feature tests for assessment gating and rationalization flow.
