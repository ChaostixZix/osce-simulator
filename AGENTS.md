# Repository Guidelines

## Project Structure & Module Organization
- Primary app: `webapp/` (Laravel + Vue via Inertia). Key dirs: `app/`, `database/`, `resources/`, `routes/`, `public/`, `tests/`.
- Legacy CLI: repository root (Node.js ESM). Entry `app.js`; helpers in `utils/`; sample data in `cases/`; tests in `test/`.
- Docs & config: `README.md`, `INTEGRATION_TESTS_SUMMARY.md`, `webapp/GEMINI.md`, `.env.example` (root and `webapp/`). Never commit real secrets.

## Build, Test, and Development Commands
- Webapp (Laravel + Vue):
  - `cd webapp && composer install && npm install` — install backend and frontend deps.
  - `composer dev` — run Laravel, queue, logs, and Vite concurrently.
  - `npm run dev` / `npm run build` — Vite dev server / production build.
  - `php artisan serve` — serve backend (if not using `composer dev`).
  - `php artisan migrate` (optionally `--seed`) — prepare database.
  - `composer test` — run Pest/PHPUnit tests.
- CLI (Node.js at repo root):
  - `npm start` or `npm run dev` — run the CLI (`app.js`).
  - `npm test` / `npm run test:watch` — run Vitest once / watch mode.
  - `npm run validate-cases` — validate case JSONs in `cases/`.
  - `npm run health` — quick environment sanity check.

## Coding Style & Naming Conventions
- PHP (webapp): follow PSR‑12; classes and Controllers in `App\` use PascalCase; tests mirror namespaces under `tests/`. Use Laravel conventions for migrations and routes.
- JS/TS: 2‑space indentation, ESM modules. Variables/functions camelCase; classes PascalCase. Vue components under `resources/` use PascalCase filenames. 
- Lint/format (webapp): `npm run lint` (ESLint) and `npm run format` / `format:check` (Prettier). Keep imports ordered and Tailwind classes formatted.

## Testing Guidelines
- Webapp: write Pest tests in `webapp/tests/Unit` or `webapp/tests/Feature` (`*Test.php`). Run with `composer test`.
- CLI: place tests in `test/` as `*.test.js`; prefer small, isolated units. Run `npm test` or `npm run test:watch`.

## Commit & Pull Request Guidelines
- Commits: imperative mood, concise scope. Prefer Conventional Commits (e.g., `feat: add triage timer`, `fix: prevent null vitals`).
- PRs: include purpose, linked issues, setup/verification steps, and screenshots for UI changes. Update docs and `.env.example` when config changes.

## Security & Configuration Tips
- Do not commit secrets. Use `.env` and keep `.env.example` updated. For AI features, set `GEMINI_API_KEY` and `GEMINI_MODEL` in `webapp/.env`.
- Validate case data before merging: `npm run validate-cases`.

