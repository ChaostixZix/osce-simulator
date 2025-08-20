# Repository Guidelines

## Project Structure & Module Organization
- Primary app: `webapp/` (Laravel + Vue via Inertia). Key dirs: `app/`, `resources/`, `routes/`, `database/`, `public/`, `tests/`.
- Legacy CLI: repository root (Node.js ESM). Entry: `app.js`; helpers: `utils/`; sample data: `cases/`; tests: `test/`.
- Docs & config: `README.md`, `INTEGRATION_TESTS_SUMMARY.md`, `webapp/GEMINI.md`, `.env.example` (root and `webapp/`). Never commit real secrets.

## Architecture Overview
- Layers: Backend (Laravel), Frontend (Vue via Inertia), Data (MySQL/SQLite via Eloquent), and a legacy Node CLI.
- Backend: routes in `webapp/routes/web.php` and `webapp/routes/api.php`; controllers in `webapp/app/Http/Controllers`; models in `webapp/app/Models`; jobs/listeners in `webapp/app/Jobs` and `webapp/app/Listeners`.
- Frontend: Vue pages/components under `webapp/resources/`; assets built with Vite; served through Inertia responses.
- Data: migrations in `webapp/database/migrations`; seeders in `webapp/database/seeders`.
- CLI: independent from the webapp; reads `cases/` and uses `utils/`. Keep it decoupled from Laravel code.
- Request flow: Browser → Laravel route → Controller returns Inertia view → Vue page mounts. API calls hit `routes/api.php` and return JSON.
- Background work: Use Laravel queue for long-running tasks (`composer dev` runs the queue worker). Dispatch jobs from controllers/services.

## Build, Test, and Development Commands
- Webapp setup: `cd webapp && composer install && npm install` — install PHP/JS deps.
- Run app: `composer dev` — Laravel, queue, logs, and Vite concurrently. Alt: `php artisan serve` and `npm run dev`.
- DB: `php artisan migrate` (optionally `--seed`).
- Webapp tests: `composer test` (Pest/PHPUnit).
- Frontend build: `npm run dev` (Vite) / `npm run build` (prod).
- CLI run: `npm start` or `npm run dev`.
- CLI tests: `npm test` / `npm run test:watch` (Vitest).
- Utilities: `npm run validate-cases` (check `cases/`), `npm run health` (env sanity check).

## Coding Style & Naming Conventions
- PHP: PSR‑12; follow Laravel conventions for Controllers, models, migrations, and routes. Classes use PascalCase.
- JS/TS: 2‑space indent, ESM modules. Variables/functions camelCase; classes PascalCase. Vue components in `webapp/resources/` use PascalCase filenames.
- Lint/format (webapp): `npm run lint`, `npm run format`, `npm run format:check`. Keep imports ordered and Tailwind classes tidy.

## Testing Guidelines
- Webapp: place tests in `webapp/tests/Unit` or `webapp/tests/Feature` as `*Test.php`. Run with `composer test`.
- CLI: tests in `test/` as `*.test.js` (Vitest). Prefer small, isolated units.
- Aim for meaningful coverage alongside new or changed code.

## Commit & Pull Request Guidelines
- Commits: imperative mood using Conventional Commits (e.g., `feat: add triage timer`, `fix: prevent null vitals`). Keep scope concise.
- PRs: include purpose, linked issues, setup/verification steps, and screenshots for UI changes. Update docs and `.env.example` when config changes.

## Security & Configuration Tips
- Do not commit secrets. Use `.env`; keep `.env.example` updated. For AI features, set `GEMINI_API_KEY` and `GEMINI_MODEL` in `webapp/.env`.
- Validate case JSONs before merging: `npm run validate-cases`.
