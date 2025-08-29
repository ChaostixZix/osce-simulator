Feature Slug: migrate-react

Objective
- Complete the migration of Inertia frontend from Vue to React using Vibe UI KIT while keeping backend stable. Ship in small, verifiable phases.

Phase 0 — Prep (baseline)
- [ ] Verify React boot works: `resources/js/app.jsx` resolves from `resources/js/pages/**/*.jsx` and renders a sample page.
- [ ] Create/verify a base React Layout (`resources/js/layouts/AppLayout.jsx`) with header/sidebar placeholders.
- [ ] Ensure Vite alias for `@vibe-kanban/ui-kit` points to the local kit or package; import a Button to confirm styling.
- [ ] Document ports in `.env` (`VITE_DEV_PORT=5173`, HMR host if needed).

Phase 1 — Shell & Navigation
- [ ] Implement AppShell components using Vibe UI KIT (Navbar, Sidebar, Container).
- [ ] Wire up `<Link>` navigation via Inertia React; add a simple route list page if needed.
- [ ] Migrate landing and dashboard pages to React with minimal content.

Phase 2 — OSCE Core
- [ ] Migrate `GET /osce` page (index/board) to React: load cases and sessions via existing endpoints; render with Vibe UI components.
- [ ] Migrate `GET /osce/chat/{session}` to React: chat UI with message list, input, send action hitting `POST /api/osce/chat/message`.
- [ ] Confirm timers and session actions use Inertia/axios via controllers (no direct Gemini calls).

Phase 3 — Assessment
- [ ] Migrate assess trigger: `POST /api/osce/sessions/{session}/assess` (button + progress state).
- [ ] Migrate status/result views: `GET /api/osce/sessions/{session}/status`, `GET /api/osce/sessions/{session}/results`, `GET /osce/results/{session}`.
- [ ] Use Vibe UI components for tables/cards; keep relative time utilities simple.

Phase 4 — Rationalization & Settings
- [ ] Migrate `GET /osce/rationalization/{session}` to React.
- [ ] Migrate settings/profile pages referenced by `routes/settings.php` to React where applicable.

Phase 5 — Cleanup & Deps
- [ ] Remove Vue-only deps: `@inertiajs/vue3`, `@vitejs/plugin-vue`, `vue`, `@tiptap/vue-3`, `lucide-vue-next`, etc. (after all pages migrated).
- [ ] Remove `resources/js/pages-vue-backup`, `app.ts.vue-backup`, `ssr.ts.vue-backup`.
- [ ] Simplify `vite.config.ts` to React-only aliases; keep Tailwind and Laravel plugin.
- [ ] Run `npm prune && npm dedupe` and verify builds.

Verification Checklist
- [ ] All routes in `webapp/routes/web.php` render React pages without console errors.
- [ ] No Vue runtime present in prod build (`dist` contains no `vue` chunks).
- [ ] `composer dev` starts server, queue, logs, and Vite without failures.
- [ ] UI components are from Vibe UI KIT; Tailwind styles pass visual smoke check.
- [ ] PHP tests pass: `composer test`.

Notes & Tips
- Keep PRs small (per route or feature) to speed reviews.
- Use Inertia partial reloads for infinite scroll and updates.
- Keep Gemini calls server-side only; React pages consume existing JSON/Inertia props.

