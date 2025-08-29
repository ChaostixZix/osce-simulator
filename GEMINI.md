# Gemini Integration — Dev Rules & Setup (Vue → React Migration)

This document consolidates rules and setup for Gemini usage in this Laravel + Inertia SPA. We are migrating the frontend from Vue to React using the Vibe UI KIT (see PR #62). Backend Gemini services and APIs remain unchanged; React pages call the same Laravel endpoints.

## Core Architecture & Development Principles

### ByteRover Integration (Critical)
- **Always use byterover-retrieve-knowledge tool** to get related context before any tasks
- **Always use byterover-store-knowledge** to store all critical information after successful tasks
- Document implementation patterns, error resolutions, API configurations, and testing patterns

### Laravel Boost MCP Tools (Required)
- **Always use Laravel Boost MCP tools when possible** for Laravel-specific operations:
  - Database queries and schema inspection
  - Application configuration retrieval  
  - Route analysis and Artisan commands
  - Error logging and debugging
  - Documentation searches using `search-docs` tool

### Inertia.js Architecture (Primary)
- **Always use Inertia.js Architecture whenever possible:**
  - Use `Inertia.get()`, `Inertia.post()`, `Inertia.put()`, `Inertia.delete()` instead of regular APIs
  - Leverage `Inertia.visit()` for navigation
  - Use `router.visit()` with proper options for form submissions
  - Check todos to ensure Inertia architecture is used first before considering alternatives
  - For new UI, prefer React + Inertia with Vibe UI KIT; legacy Vue pages remain until migrated.

## Technology Stack & Versions
- **PHP** - 8.2.29
- **Laravel Framework** - v12
- **Inertia Laravel** - v2
- **Vue.js** - v3 (legacy)
- **React** - In progress (Vibe UI KIT)
- **Tailwind CSS** - v4
- **Pest** - v3
- **Laravel Pint** - v1

## Development Workflow

### Component & Page Management
- React (preferred for new work): Use Inertia React + Vibe UI KIT components; keep Tailwind styles consistent.
- Vue (legacy): shadcn-vue remains; only migrate when converting an entire page.
- New Page Creation Process:
  1. Create React page in `resources/js/Pages/` (or feature subfolder) using Inertia React.
  2. Add navigation entry in the appropriate layout/sidebar.
  3. Create/adjust Laravel Controller to return Inertia response.
  4. Add routes in `web.php`.

## Gemini Configuration (env)

Set in `webapp/.env` (see `.env.example`):

```
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash
GEMINI_TIMEOUT=30                 # seconds (optional)
GEMINI_RATE_LIMIT=60              # requests/minute (optional)
GEMINI_MAX_CONCURRENT=5           # in-flight requests (optional)
GEMINI_FALLBACK_ENABLED=true      # allow degraded results on parse issues (optional)
```

Notes
- `config/services.php` and `config/gemini.php` both read `GEMINI_MODEL`. Default differs by file; set the env var explicitly to avoid mismatch.
- Never commit real keys. Keep `.env.example` updated only with placeholders.
- Dev DB defaults to SQLite; long-running Gemini work should run via the queue worker (dev script starts one).

### Error Handling
- Use Browser Logs (Laravel Boost MCP Tools) to check latest errors
- Confirm specific error before fixing (check last 2 entries only)
- Don't assume - always verify the exact error first

### Testing (Critical)
- **Tests first approach:** Write tests first, fix code to pass those tests
- Only proceed to next task once tests are passing
- Use Pest for all testing: `php artisan make:test --pest <name>`
- Run minimal tests with filters before finalizing: `php artisan test --filter=testName`

## Laravel Specific Rules

### Code Generation
- Use `php artisan make:` commands to create new files
- Pass `--no-interaction` and correct `--options` to Artisan commands
- Use Eloquent relationships with return type hints
- Always create Form Request classes for validation
- Use queued jobs with `ShouldQueue` interface for time-consuming operations

### Database
- Prefer Eloquent models and relationships over raw queries
- Use eager loading to prevent N+1 query problems
- Use `Model::query()` instead of `DB::`

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`
- Use `bootstrap/app.php` for middleware, exceptions, and routing
- Commands in `app/Console/Commands/` auto-register
- Use `casts()` method on models rather than `$casts` property

## Frontend Rules

### Inertia (Vue legacy + React new)
- Use `<Link>`/`router.visit()` for navigation.
- Use Inertia forms or `router.post/put/delete` for mutations.
- Avoid raw fetch calls from pages; go through controllers and services.

### Tailwind CSS v4
- Use Tailwind v4 syntax: `@import "tailwindcss"` not `@tailwind` directives
- Use replacement utilities (e.g., `shrink-*` not `flex-shrink-*`)
- Use gap utilities for spacing instead of margins
- Support dark mode with `dark:` classes when existing components do

## Code Quality & Standards

### PHP Standards
- Use curly braces for all control structures
- Use PHP 8 constructor property promotion
- Always use explicit return type declarations
- Run `vendor/bin/pint --dirty` before finalizing changes

### Documentation
- Use PHPDoc blocks over inline comments
- Add useful array shape type definitions
- Use TitleCase for Enum keys

### File References in Rules
- Use `[filename](mdc:path/to/file)` format for file references
- Include both DO and DON'T examples in code blocks
- Keep rules DRY by cross-referencing related rules

## AI Prompt Creation Guidelines

When creating AI prompts for implementation tasks:
- Always provide conversation context explaining WHY changes are needed
- Include relevant existing codebase functions/models/structure as context
- Reference specific files, methods, and database schemas that exist
- Keep code examples concise - show structure, not full implementations
- Explain the educational/technical problem being solved
- Ensure next AI understands the reasoning behind architectural decisions

## Development Commands

### Common Operations
- **Start development:** `npm run dev` or `composer run dev`
- **Build for production:** `npm run build`
- **Run tests:** `php artisan test`
- **Format code:** `vendor/bin/pint --dirty`
- **List Artisan commands:** Use `list-artisan-commands` tool

### Error Resolution
- Check browser logs with Laravel Boost MCP tools
- Use `tinker` tool for debugging PHP code
- Use `database-query` tool for database inspection
- Search documentation with `search-docs` tool before other approaches

## Rule Maintenance

### Self-Improvement Triggers
- New code patterns not covered by existing rules
- Repeated similar implementations across files
- Common error patterns that could be prevented
- New libraries or tools being used consistently

### Rule Updates
- Add new rules when patterns are used in 3+ files
- Modify existing rules when better examples exist
- Remove outdated patterns and update references
- Cross-reference related rules for consistency

## Where Gemini Is Used (code references)

- `app/Services/AiPatientService.php`: AI patient chat responses (Gemini 1.5 flash endpoint).
- `app/Services/GeminiService.php`: Core Gemini client with web search grounding and parsing.
- `app/Services/AiAssessorService.php`: Session scoring and assessment via Gemini.
- `app/Services/AreaAssessor.php`: Area-specific clinical assessments via Gemini.
- `app/Services/RationalizationEvaluationService.php`: Post-session rationalization using Gemini.
- `app/Services/ResultReducer.php`: Bundles model metadata into results (reads configured model).
- `app/Http/Controllers/OsceAssessmentController.php`: Exposes assess/status/results endpoints (uses configured model).

## API Endpoints (Gemini-backed)

- `POST /api/osce/sessions/{session}/assess` → runs assessment (queue-backed for long tasks).
- `GET  /api/osce/sessions/{session}/status` → assessment status.
- `GET  /api/osce/sessions/{session}/results` → assessment results view/data.
- OSCE Chat endpoints: `POST /api/osce/chat/start`, `POST /api/osce/chat/message`, `GET /api/osce/chat/history/{session}`.

## Troubleshooting

- 401/403 from Gemini: verify `GEMINI_API_KEY` and that the key supports the selected `GEMINI_MODEL`.
- Timeouts: raise `GEMINI_TIMEOUT` or run via queues to prevent request timeouts.
- Rate limiting: tune `GEMINI_RATE_LIMIT` and `GEMINI_MAX_CONCURRENT` to your key limits.
- Parsing errors: `GEMINI_FALLBACK_ENABLED=true` allows structured fallback; check logs for raw content.
- Tests failing due to missing API key: tests can set `config(['services.gemini.api_key' => null])` to disable real calls (see tests under `webapp/tests/Feature/*Assessment*Test.php`).

## Migration Note (PR #62)

- React UI migration does not change Gemini configuration or routes.
- Continue calling Laravel endpoints from React (Inertia) pages; no direct client-side Gemini calls.
- Keep all Gemini work in services/controllers; do not embed API keys in frontend code.
