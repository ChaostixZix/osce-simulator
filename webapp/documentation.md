# Vibe Kanban Documentation

This document summarizes the current state of the Vibe Kanban application, covering the architecture, tooling, setup steps, and day-to-day workflows. Refer to this file before re-indexing the project with external tooling.

## Overview

Vibe Kanban is an Inertia-powered Laravel SPA that simulates Objective Structured Clinical Examinations (OSCE). It combines a Laravel 12 backend, a React frontend (with legacy Vue components), AI-assisted assessments, and real-time collaboration to help learners practice clinical reasoning.

Key capabilities include:
- Guided OSCE case sessions with timers, chat, and ordered investigations.
- AI-driven patient personas and assessment pipelines that score and summarize trainee performance.
- Post-session rationalization workflows and result review pages.
- WorkOS-based authentication and multi-tenant friendly session management.

## Architecture

### Backend (Laravel)
- **Framework:** Laravel 12 with Inertia.js adapter (`inertiajs/inertia-laravel`).
- **Domain layer:** Service classes in `app/Services` orchestrate AI providers, assessment queues, and result reduction (e.g. `AiPatientService`, `AiAssessorService`, `AssessmentQueueService`).
- **Controllers:** HTTP endpoints live in `app/Http/Controllers`, including `OsceController`, `OsceChatController`, `OsceAssessmentController`, `RationalizationController`, and settings controllers.
- **Authentication:** WorkOS integration (`laravel/workos`) handles SSO/login flows.
- **Messaging:** Laravel Reverb provides WebSocket broadcasting for chat and presence; Redis backs queues, cache, and sessions (`QUEUE_CONNECTION=redis`).
- **AI Providers:** Gemini (default) and Azure OpenAI are configured through `UniversalAIService` / `GeminiService` with provider selection via the `AI_PROVIDER` env variable.

### Frontend (Inertia + React)
- **Entry point:** `resources/js/app.jsx` boots the Inertia React app and resolves pages from `resources/js/pages`.
- **UI kit:** Components leverage Vibe UI KIT primitives plus the custom gaming design system documented in `CLAUDE.md` / `DESIGN_SYSTEM.md` (cyber borders, gradient cards, etc.).
- **State & hooks:** Shared contexts and hooks reside under `resources/js/contexts`, `resources/js/hooks`, and `resources/js/lib`.
- **Legacy Vue:** Existing Vue components remain under `resources/js/components` and related folders; keep them stable until migrated to React.

### Data & Infrastructure
- **Database:** PostgreSQL in production (SQLite supported locally). Migrations live in `database/migrations`.
- **Queues & cache:** Redis via Predis powers queues, sessions, and cache layers.
- **Realtime:** `php artisan reverb:start` hosts the WebSocket server; frontend connects with Laravel Echo and Pusher-compatible clients.
- **Storage:** Local filesystem in development; configure S3-compatible storage via `.env` for production.

## Setup

### Prerequisites
- PHP 8.2+
- Composer 2.6+
- Bun 1.x (preferred for JS tooling) with Node.js 20 runtime
- Redis 6+ (queues/cache/session)
- PostgreSQL 14+ (or SQLite for local development)
- WorkOS API credentials
- Gemini API key (and optional Azure OpenAI credentials)

### Installation Steps
1. **Clone the repository**
   ```bash
   git clone <repo-url>
   cd vibe-kanban/webapp
   ```
2. **Install PHP dependencies**
   ```bash
   composer install
   ```
3. **Install JS dependencies** (prefer Bun; npm works as fallback)
   ```bash
   bun install
   ```
4. **Environment configuration**
   ```bash
   cp .env.example .env
   ```
   Update values for database, Redis, WorkOS, AI providers, and Reverb credentials:
   - `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
   - `WORKOS_CLIENT_ID`, `WORKOS_API_KEY`, `WORKOS_REDIRECT_URL`
   - `AI_PROVIDER`, `GEMINI_API_KEY`, `OPENAI_AZURE_*`
   - `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_SERVER_PORT`
5. **Generate application key & run migrations**
   ```bash
   php artisan key:generate
   php artisan migrate
   ```
6. **(Optional) Link storage for uploads**
   ```bash
   php artisan storage:link
   ```

## Key Directories & Files
- `app/Models` – Eloquent models for users, OSCE entities (cases, sessions, messages, ordered tests, rationalizations).
- `app/Services` – AI orchestration, assessment queue management, and result reducers.
- `app/Http/Controllers` – Entry points for dashboard, OSCE session flow, chat, assessments, rationalizations, and settings.
- `routes/web.php` – Inertia routes for landing, dashboard, OSCE flows, and settings sections.
- `resources/js/pages` – React Inertia pages (Dashboard, OSCE session, chat, rationalization, settings).
- `resources/js/components` – Shared UI pieces (mix of Vue + React) including design-system compliant elements.
- `resources/js/layouts` – Layout wrappers and navigation shells.
- `config/reverb.php`, `config/broadcasting.php` – Real-time server configuration.
- `documentation.md` – This file; keep updated when architecture changes.

## Development Workflow
- **Full stack dev server** – orchestrated via Composer script:
  ```bash
  composer run dev
  ```
  Runs `php artisan serve`, `queue:work`, `reverb:start`, and the Vite dev server concurrently.
- **Frontend-only dev server** – when Laravel services already running:
  ```bash
  bun run dev
  ```
- **Static builds** – build Vite assets (SSR+client) before deployments:
  ```bash
  bun run build       # client bundle
  bun run build:ssr   # client + server bundle
  ```
- **Queues & realtime (manual control)**
  ```bash
  php artisan queue:work --queue=assessments,management,default --tries=3
  php artisan reverb:start --debug
  ```
- **Linting & formatting**
  ```bash
  bun run lint
  bun run format
  bun run format:check
  ```
- **Design system** – follow `CLAUDE.md`/`DESIGN_SYSTEM.md` guidelines (cyber borders, gradient cards, hover transitions, status indicators) for all frontend work.

## Testing & Quality Gates
- **PHP tests** – Pest/PHPUnit suite:
  ```bash
  php artisan test
  ```
- **JavaScript/TypeScript checks** – ESLint and Prettier via Bun (commands above).
- **Manual QA** – Verify OSCE session lifecycle (start → chat → rationalization → results), WorkOS login, real-time chat presence, and AI assessment flows.
- **Queues/Reverb** – Ensure Redis is running before queue or websocket tests.

## Troubleshooting
- **WorkOS login loops** – confirm callback URL matches `WORKOS_REDIRECT_URL` and `APP_URL`.
- **Missing realtime updates** – check `php artisan reverb:start` output, confirm Reverb app credentials, and ensure `VITE_DEV_SERVER_URL` is set when accessing from non-localhost devices.
- **Queue stalls** – verify Redis connectivity and that `queue:work` is running on the `assessments` queue; clear failed jobs with `php artisan queue:flush`.
- **AI provider errors** – confirm `AI_PROVIDER` matches configured credentials; review `storage/logs/laravel.log` for provider-specific messages.
- **Cache/config drift** – clear caches after env changes:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan route:clear
  ```

Keep this documentation current when adding features, adjusting infrastructure, or modifying developer workflows. When new knowledge is captured, update this file before initiating fresh Gemini CLI indexing to avoid redundant reprocessing.
