# Repository Guidelines

## Project Structure & Module Organization
- `app/`: Laravel application code (HTTP, Models, Console, etc.).
- `routes/`: Route definitions (`web.php`, `api.php`).
- `resources/js/`: Inertia + Vue 3 app (components, pages, layouts, `app.ts`, `ssr.ts`).
- `resources/css/`: Tailwind styles; built via Vite.
- `public/`: Public assets and entry point.
- `database/`: Migrations, factories, seeders.
- `tests/`: Pest tests (`Feature/`, `Unit/`).
- Tooling configs: `vite.config.ts`, `eslint.config.js`, `.prettierrc`, `phpunit.xml`, `composer.json`, `package.json`.

## Build, Test, and Development Commands
- `composer dev`: Run full-stack dev (Laravel server, queue, logs, Vite).
- `composer dev:ssr`: Dev with server-side rendering enabled.
- `npm run dev`: Start Vite dev server for frontend only.
- `npm run build`: Build production assets (and SSR with `build:ssr`).
- `composer test` or `php artisan test`: Run PHP/Pest test suite.
- Common setup: `composer install && npm install && cp .env.example .env && php artisan key:generate`.

## Coding Style & Naming Conventions
- Indentation: 4 spaces (`.editorconfig`, Prettier `tabWidth: 4`).
- PHP: Follow PSR-12. Format with Laravel Pint: `./vendor/bin/pint`.
- Vue/TS: Lint with `npm run lint` (ESLint + Vue TS). Format with `npm run format` / `format:check` (Prettier).
- Names: PascalCase Vue components, camelCase variables/functions, StudlyCase PHP classes, snake_case DB columns.
- Paths: Pages under `resources/js/pages`, shared UI under `resources/js/components`.

## Testing Guidelines
- Framework: Pest on top of PHPUnit. Place feature tests in `tests/Feature`, unit tests in `tests/Unit`.
- Naming: `SomethingTest.php`. Pest style examples: `it('shows dashboard', function () { /* ... */ });`.
- Run locally: `composer test`. Keep tests deterministic; seed data via factories.

## Commit & Pull Request Guidelines
- Commits: Prefer Conventional Commits (e.g., `feat:`, `fix:`, `chore:`). Keep messages imperative and scoped.
- PRs: Include a clear description, linked issues (e.g., `Closes #123`), test coverage notes, and screenshots for UI changes.
- CI/readiness: Ensure `composer test`, `npm run lint`, and `npm run build` pass. Update docs when behavior changes.

## Security & Configuration Tips
- Never commit secrets. Configure environment in `.env` (DB, queues). Generate app key with `php artisan key:generate`.
- Migrate/seed using `php artisan migrate --seed`. Use queues (`php artisan queue:listen`) for async work during dev.
