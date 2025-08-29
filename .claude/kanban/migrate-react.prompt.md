Title: Migration Plan — Move Inertia Frontend from Vue 3 to React (Vibe UI KIT) for OSCE app

Purpose
- Migrate the SPA frontend to React to standardize on Vibe UI KIT, simplify component reuse, and accelerate UI iteration while keeping Laravel + Inertia architecture and backend services unchanged.

Scope
- Convert Inertia pages and layouts from Vue to React incrementally.
- Keep backend routes, controllers, jobs, and services as-is; only adjust Inertia responses if page component paths change.
- Maintain Tailwind v4 styling; replace shadcn-vue with Vibe UI KIT React components.
- Ensure dev workflow remains `composer dev` / `npm run dev` with Vite HMR.

Out of Scope
- Backend data models, migrations, and queue behaviors.
- Changing Gemini service contracts or endpoints.
- SSR enablement (keep disabled during migration).

Architecture & Current State
- Backend: Laravel 12, Inertia Laravel 2, queue workers (database), SQLite in dev.
- Frontend: Mixed stack. React is configured and bootstrapped at `resources/js/app.jsx` resolving React pages from `resources/js/pages/**/*.jsx`. Legacy Vue pages are under `resources/js/pages-vue-backup` and backup files like `app.ts.vue-backup`.
- Vite: `vite.config.ts` loads `@vitejs/plugin-react`, Tailwind, and `laravel-vite-plugin`. Input: `resources/js/app.jsx`. HMR/ports configured via `.env`.
- UI Kit: Temporary alias `@vibe-kanban/ui-kit` present in Vite config; React pages should import components from this kit.

Key Files & References
- Frontend entry: `webapp/resources/js/app.jsx`
- React pages root: `webapp/resources/js/pages/`
- Layouts and components: `webapp/resources/js/layouts`, `webapp/resources/js/components`
- Vite config: `webapp/vite.config.ts`
- NPM scripts: `webapp/package.json`
- Laravel routes: `webapp/routes/web.php`
- Inertia controllers: under `webapp/app/Http/Controllers/*` (e.g., `OsceController`, `OsceAssessmentController`, `LandingController`, `DashboardController`)

Constraints
- Use Inertia React (`@inertiajs/react`) for navigation and forms; avoid raw fetch.
- Use Vibe UI KIT React components; keep Tailwind classes tidy.
- Do not mix Vue and React within a page. Full-page swaps only.
- Keep ports consistent: Laravel 8001, Vite 5173 (configurable via `.env`).
- No client-side Gemini calls; use existing Laravel endpoints.

Error Handling & Logging
- Surface server-side validation/errors via Inertia props.
- Log Gemini and assessment errors on the server (existing services). Do not expose API keys client-side.
- Guard optional tooling in dev scripts (pail) to avoid crashes in environments without it.

Acceptance Criteria
1) App boots via React entry (`resources/js/app.jsx`) and all targeted pages render using React components.
2) Navigation, forms, and state behave the same as legacy pages with Inertia React.
3) New UI uses Vibe UI KIT components; Tailwind styles consistent; no shadcn-vue usage on migrated pages.
4) `vite.config.ts` only requires React plugin for migrated build; SSR stays disabled.
5) No Vue runtime errors in console on migrated routes; Vue-only deps can be removed in cleanup.
6) Dev scripts and `composer dev` continue to run Laravel, queues, logs, and Vite smoothly.

Phased Migration (High-Level)
- Phase 0 — Prep: Confirm React boot, align Vite alias, baseline layout.
- Phase 1 — Shell: Convert base layouts, navbar/sidebar, and route skeletons.
- Phase 2 — OSCE Core: Migrate `osce` index and chat pages.
- Phase 3 — Assessment: Migrate assessment start/status/results views.
- Phase 4 — Remaining pages: Dashboard, landing, rationalization, settings.
- Phase 5 — Cleanup: Remove Vue deps, delete backups, simplify Vite config.

Risks & Mitigations
- Mixed-kit UX drift → Define a minimal design token bridge; prefer Vibe UI KIT defaults.
- Large PRs → Ship per-route or per-feature PRs; keep diffs contained.
- Inertia prop drift → Use existing controller responses; only adjust component names/paths.

Quick Commands
- Dev: `composer dev` (Laravel, queue, logs, Vite)
- Frontend: `npm run dev` / `npm run build`
- Tests: `composer test`

