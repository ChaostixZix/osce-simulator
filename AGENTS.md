# Repository Guidelines

## Project Structure & Module Organization
- Primary app: `webapp/` (Laravel + Inertia; Vue today, React in migration). Key dirs: `app/`, `resources/`, `routes/`, `database/`, `public/`, `tests/`.
- Legacy CLI: repository root (Node.js ESM). Entry: `app.js`; helpers: `utils/`; sample data: `cases/`; tests: `test/`.
- Docs & config: `README.md`, `INTEGRATION_TESTS_SUMMARY.md`, `webapp/GEMINI.md`, `.env.example` (root and `webapp/`). Never commit real secrets.

## Architecture Overview
- Layers: Backend (Laravel), Frontend (Inertia SPA — Vue currently, React migration in progress using Vibe UI KIT), Data (MySQL/SQLite via Eloquent), and a legacy Node CLI.
- Backend: routes in `webapp/routes/web.php` and `webapp/routes/api.php`; controllers in `webapp/app/Http/Controllers`; models in `webapp/app/Models`; jobs/listeners in `webapp/app/Jobs` and `webapp/app/Listeners`.
- Frontend: Inertia pages/components under `webapp/resources/`. Legacy pages are Vue; new/refactored pages use React with Vibe UI KIT. Assets built with Vite; served through Inertia responses.
- Data: migrations in `webapp/database/migrations`; seeders in `webapp/database/seeders`.
- CLI: independent from the webapp; reads `cases/` and uses `utils/`. Keep it decoupled from Laravel code.
- Request flow: Browser → Laravel route → Controller returns Inertia view → Vue page mounts. API calls hit `routes/api.php` and return JSON.
- Background work: Use Laravel queue for long-running tasks (`composer dev` runs the queue worker). Dispatch jobs from controllers/services.

## Build, Test, and Development Commands
- Webapp setup: `cd webapp && composer install && npm install` — install PHP/JS deps.
- Run app: `composer dev` — Laravel, queue, logs, and Vite concurrently. Alt: `php artisan serve` and `npm run dev`.
- DB: `php artisan migrate` (optionally `--seed`).
- Webapp tests: `composer test` (Pest/PHPUnit).
- Frontend build: `npm run dev` (Vite) / `npm run build` (prod). For React pages, ensure Inertia React is installed; Vue pages continue to work unchanged.
- CLI run: `npm start` or `npm run dev`.
- CLI tests: `npm test` / `npm run test:watch` (Vitest).
- Utilities: `npm run validate-cases` (check `cases/`), `npm run health` (env sanity check).

## Coding Style & Naming Conventions
- PHP: PSR‑12; follow Laravel conventions for Controllers, models, migrations, and routes. Classes use PascalCase.
- JS/TS: 2‑space indent, ESM modules. Variables/functions camelCase; classes PascalCase.
- Vue (legacy): components in `webapp/resources/` follow PascalCase filenames.
- React (new): components/pages in `webapp/resources/` follow PascalCase filenames. Use Vibe UI KIT components where applicable.
- Lint/format (webapp): `npm run lint`, `npm run format`, `npm run format:check`. Keep imports ordered and Tailwind classes tidy.

## Inertia Interaction Rules (React)
- Use `@inertiajs/react` exclusively for SPA interactions:
  - Navigations: `<Link>` and `router.visit(url, options)`.
  - Mutations: `useForm` or `router.post/put/patch/delete` — avoid raw `fetch`/`axios`.
  - Partial reload: `router.reload({ only: ['propA','propB'], preserveScroll: true })`.
  - Forms: prefer `useForm` for automatic CSRF, errors, progress, and state.
- Only call plain JSON endpoints with `fetch` if you are streaming or rendering raw JSON without Inertia navigation; otherwise, stick to Inertia.
- Do not manually add CSRF headers when using Inertia — it is handled for you.
- For uploads: use `useForm` with `transform((data) => formData)`.
- Keep Vue and React isolated per page; do not mix adapters.

Example (useForm):
```jsx
import { useForm, Link, router } from '@inertiajs/react'

const { data, setData, post, processing, errors } = useForm({ osce_case_id: caseId })
const start = () => post('/api/osce/sessions/start', {
  preserveScroll: true,
  onSuccess: (page) => router.visit(`/osce/chat/${page.props?.session?.id ?? ''}`)
})
```

Example (router.post):
```jsx
import { router } from '@inertiajs/react'
router.post(`/api/osce/sessions/${sessionId}/assess`, { force: true }, { preserveScroll: true })
```

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

## Agent Role & Prompt-First Workflow

- Primary role: General coding and problem‑solving in this repo (implement, refactor, debug, test). Prompt building is an opt‑in macro.
- Frontend direction: Prefer React + Inertia for new work; keep Vue pages stable until migrated. Do not mix Vue and React within a single page.
- Vibe Kanban: For new‑function work using prompts, create exactly three tasks (Diagnosis, Implementation, Testing) and keep artifacts in sync. Task titles and objective actions must be elaborative, detailed prompts (not short labels).
- Shared slug: Define a single kebab‑case `<feature-slug>` in Diagnosis and reuse across all agents/files.
- Filenames (store under `.claude/kanban/`):
  - `<feature-slug>.prompt.md` — Diagnosis/context prompt with rationale and detailed requirements.
  - `<feature-slug>.implementation.md` — Implementation report in table form describing changes done.
  - `<feature-slug>.tests.md` — Test plan, results, and debugging summary.
- Prompt checklist (in `<feature-slug>.prompt.md`):
  - Purpose: Why this function is needed now (business/technical reason).
  - Scope: Clear responsibilities, inputs/outputs, return types, side effects.
  - Code references: Files, classes, functions, models, routes impacted (with paths like `webapp/app/...`).
  - Constraints: Performance, security, data integrity, Inertia/Laravel conventions to follow.
  - Error handling: Expected failures, validation, logging, retries.
  - Acceptance criteria: Behavioral examples, edge cases, and success conditions.
  - Out of scope: Explicitly exclude unrelated work.
- Handoffs:
  - Implementation agent must implement strictly per `<feature-slug>.prompt.md` and produce `<feature-slug>.implementation.md`.
  - Testing agent validates against the prompt and the implementation, records results in `<feature-slug>.tests.md`.
  - Keep all three files linked via the same `<feature-slug>`.

### Human-Friendly Triggers

Default mode is general coding. Use plain language cues; I’ll ask quick follow‑ups if details are missing.

- Build a prompt (and files):
  - Say: “Blueprint this feature: <short name> …”, or “Make a prompt for <feature> …”.
  - Result: I generate the detailed prompt from the template and create `.claude/kanban` files.

- Preview the prompt first:
  - Say: “Show me the prompt first”, or “Preview the blueprint only”.
  - Result: I share the prompt text without writing files.

- General coding task (default):
  - Just describe what you need, e.g., “Add pagination to the SOAP board”, or start with “Let’s code: …”.
  - Result: I analyze, plan if needed, implement, and test — no prompt docs.

- Make a quick plan:
  - Say: “What’s the plan?” or “Plan this out for me.”
  - Result: I produce a concise step‑by‑step plan and proceed.

- Run tests:
  - Say: “Run the webapp tests” or “Run the CLI tests”.
  - Result: I run `composer test` (webapp) or `npm test` (CLI) and report.

Defaults I’ll use if you don’t specify

- Timezone: Asia/Jakarta; Autosave: 10s; Max attachment: 5 MB; Storage: local; DB: SQLite in dev.

### Vibe Kanban — Elaborative Titles and Objectives

Always write task titles and objective actions as full, self‑contained prompts so a developer can execute without guessing. Aligns with CLAUDE.md Vibe Kanban guidance.

- Titles: Use complete, descriptive prompts that name the scope, technology, and deliverables.
  - Good: "Diagnosis: Build a standalone SOAP module under /soap (Laravel + React + Inertia) — define migrations, policies, routes, controllers, and React pages; list constraints (SQL LIKE search, no OSCE integration), and acceptance criteria with UX details (autosave, timeline, infinite scroll)."
  - Bad: "Diagnosis: SOAP module".

- Objective actions: Write numbered steps with specifics — files, paths, commands, models, routes, UI behavior, validation rules, and acceptance tests. Avoid vague verbs.
  - Good: "1) Create migrations: patients, soap_notes, soap_attachments, soap_comments; run php artisan migrate; 2) Implement models with relationships and scopeLikeSearch; 3) Add policies with admin override; 4) Define routes under /soap … 5) Build Board.jsx and Page.jsx with autosave (10s) and partial reload …"
  - Bad: "Set up DB and pages".

- Cross‑link artifacts: Include the shared `<feature-slug>` and reference `.claude/kanban/<feature-slug>.prompt.md`, `.implementation.md`, `.tests.md` in each task description.

- Acceptance criteria: End every task description with explicit, testable checks covering edge cases, UX states, permissions, and data rules.

- Consistency: Mirror timezone, autosave interval, attachment limits, and SPA conventions defined here unless the task overrides them.

## AI Prompt Template — Standalone Feature Module (Laravel + React + Inertia)

Use this when asking for a laser-detailed, do-this-exactly prompt to scaffold a brand-new, isolated module. Replace all placeholders like `{{placeholder}}` before pasting into your AI/dev tool.

---

# PROMPT: Build a Standalone {{Module Name}} Module (Laravel + React + Inertia)

## Objectives (do exactly this)

* Create a new top-level module under the path `/{{module-path}}` with these pages:
  1. {{Board Page Name}}: `GET /{{module-path}}` — a dedicated board listing {{primary-entity-plural}} (not shared with other modules).
  2. {{Record Page Name}}: `GET /{{module-path}}/{{primary-entity-route-segment}}/{id}` — a full page with a primary form on top and a timeline below.
* Keep it isolated: no interaction with other modules/features unless explicitly specified.
* Autosave for the form (hybrid: on blur + every {{autosave-interval-seconds}}s) with a visible “Saving… / Saved” indicator.
* Timeline shows newest first, short preview expandable, relative timestamps, status badge (e.g., Draft/Finalized), author name, infinite scroll with loading spinner, {{draft-visibility}} drafts visible to authors, soft-deleted hidden (admins can restore).
* Finalize action locks the record for non-admins; admins can override to edit finalized items.
* Attachments: upload any file type, unlimited count, ≤{{max-attachment-mb}} MB each, download links only, cannot remove once uploaded.
* Comments: text-only, collapsed by default, lazy-load on expand.
* Search on `/{{module-path}}` via SQL LIKE across name/title + note/content fields, with filters, sorting, and pagination.
* Timezone/formatting: `{{app-timezone}}`, 24h, `DD/MM/YYYY`; relative times like “2 hours ago”.

---

## Constraints / Non-Goals

* Do not use external search engines (e.g., Meilisearch/Typesense); use SQL LIKE.
* Do not add notifications/export/print/audit trail/view tracking unless explicitly requested.
* Do not reuse UI from other modules (no cross-module Kanban or modals); this lives under `/{{module-path}}`.

---

## Step 0 — Prereqs & Conventions

* Stack: Laravel, React, Inertia.js, dev DB SQLite, storage local.
* Assume `users` table exists and `users.is_admin` boolean is available.
* App timezone: set `config/app.php` → `'timezone' => '{{app-timezone}}'`.
* Use Inertia form posts for create/update/finalize/attach. Use Inertia partial reload for infinite scroll.

---

## Step 1 — Migrations (create exactly these)

### Commands

```bash
php artisan make:migration create_{{primary-entity-table}}_table
php artisan make:migration create_{{record-table}}_table
php artisan make:migration create_{{attachment-table}}_table
php artisan make:migration create_{{comment-table}}_table
```

### Tables (fields)

**{{primary-entity-table}}**

* id, {{primary-name-field}} (string), {{primary-extra-fields}} (optional), status enum [{{primary-statuses}}] (default {{primary-default-status}}), timestamps, softDeletes.

**{{record-table}}**

* id, {{primary-entity-singular}}_id FK, author_id FK (users), form fields: {{form-fields-list}} as text/string as appropriate,
* state enum [draft, finalized] default draft, finalized_at nullable timestamp, timestamps, softDeletes.
* Indexes on `({{primary-entity-singular}}_id, created_at)` and `state`.

**{{attachment-table}}**

* id, {{record-singular}}_id FK, disk string (default `local`), path string, original_name string, size bigint, mime string nullable, timestamps.

**{{comment-table}}**

* id, {{record-singular}}_id FK, author_id FK (users), body text, timestamps, softDeletes.

Run:

```bash
php artisan migrate
```

---

## Step 2 — Eloquent Models (minimal)

Create: `app/Models/{{PrimaryEntity}}.php`, `{{Record}}.php`, `{{Attachment}}.php`, `{{Comment}}.php`.

Relationships/Scopes

* `{{PrimaryEntity}}::{{recordsRelation}}()` hasMany.
* `{{Record}}::{{primaryEntityRelation}}()`, `{{Record}}::author()` belongsTo.
* `{{Record}}::attachments()` hasMany; `{{Record}}::comments()` hasMany.
* `{{Record}}::scopeLikeSearch($q, $term)` → where configured form fields LIKE `%term%`.

---

## Step 3 — Policies (permissions)

```bash
php artisan make:policy {{Record}}Policy --model={{Record}}
```

Rules:

* view: any authenticated user (team scope as applicable).
* create: authenticated.
* update: user is admin OR (author AND state=draft).
* delete (soft): user is admin OR (author AND state=draft).
* finalize: user is admin OR (author AND state=draft).
* restore: admin only.

Register policy in `AuthServiceProvider`.

---

## Step 4 — Routes (web)

Add to `routes/web.php`:

```php
Route::middleware('auth')->group(function () {
  // NEW PAGES
  Route::get('/{{module-path}}', [{{BoardController}}::class, 'index'])->name('{{route-prefix}}.board');
  Route::get('/{{module-path}}/{{primary-entity-route-segment}}/{id}', [{{PageController}}::class, 'show'])->name('{{route-prefix}}.page');

  // RECORDS
  Route::post('/{{module-path}}/{{primary-entity-route-segment}}/{id}/records', [{{RecordController}}::class, 'store'])->name('{{route-prefix}}.store');
  Route::put('/{{module-path}}/records/{record}', [{{RecordController}}::class, 'update'])->name('{{route-prefix}}.update');
  Route::post('/{{module-path}}/records/{record}/finalize', [{{RecordController}}::class, 'finalize'])->name('{{route-prefix}}.finalize');
  Route::delete('/{{module-path}}/records/{record}', [{{RecordController}}::class, 'destroy'])->name('{{route-prefix}}.destroy');
  Route::post('/{{module-path}}/records/{record}/restore', [{{RecordController}}::class, 'restore'])
       ->middleware('can:restore,record')->name('{{route-prefix}}.restore'); // admin only

  // ATTACHMENTS
  Route::post('/{{module-path}}/records/{record}/attachments', [{{AttachmentController}}::class, 'store'])->name('{{route-prefix}}.attach');

  // COMMENTS (lazy JSON)
  Route::get('/{{module-path}}/records/{record}/comments', [{{CommentController}}::class, 'index'])->name('{{route-prefix}}.comments.index');
  Route::post('/{{module-path}}/records/{record}/comments', [{{CommentController}}::class, 'store'])->name('{{route-prefix}}.comments.store');
});
```

---

## Step 5 — Controllers (behavior)

Create:

```bash
php artisan make:controller {{BoardController}}
php artisan make:controller {{PageController}}
php artisan make:controller {{RecordController}}
php artisan make:controller {{AttachmentController}}
php artisan make:controller {{CommentController}}
```

{{BoardController}}@index (GET `/{{module-path}}`)

* Accept query: `status` (domain-specific), `search` (name/title), `sort` (e.g., name|created|latest), `page`.
* Query `{{PrimaryEntity}}` with status filter, `name LIKE %q%` search, `withCount('{{recordsRelation}}')`, eager-load latest 1 record for preview.
* Sorting: by name, created_at desc, or latest related record via subquery `max({{record-table}}.created_at)`.
* Return Inertia page `{{Module}}/Board` with paginated data, filters, and keep query string.

{{PageController}}@show (GET `/{{module-path}}/{{primary-entity-route-segment}}/{id}`)

* Load `{{PrimaryEntity}}`.
* Load first page of `{{recordsRelation}}` (10 per page), ordered `created_at desc`, exclude soft-deleted for non-admins.
* Return Inertia page `{{Module}}/Page` with props: entity, records, `can.admin`, `tz: '{{app-timezone}}'`.

{{RecordController}}@store (create draft or new)

* Validate required form fields: {{form-fields-list}}.
* Create record with `state='draft'`, `author_id=auth()->id()`, `{{primary-entity-singular}}_id`.
* Redirect back (Inertia) with flash; share the new record id in page props for frontend binding.
* Optional: if avoiding multiple drafts per author/entity, upsert into existing draft.

{{RecordController}}@update (edit draft / admin override)

* Authorize `update`.
* Validate all fields; update and return back (preserve scroll/state).

{{RecordController}}@finalize

* Authorize `finalize`.
* Ensure required fields are non-empty; set `state='finalized'`, `finalized_at=now()`; redirect back.

{{RecordController}}@destroy

* Authorize `delete`; soft-delete; redirect back (hidden for non-admins).

{{RecordController}}@restore (admin)

* `onlyTrashed()->findOrFail()` then `restore()`.

{{AttachmentController}}@store

* Authorize `update` (draft or admin override).
* Validate: `files.* => 'file|max:{{max-attachment-kb}}'`.
* Store each file to `{{module-path}}/{record_id}` on `local`; create metadata rows; redirect back.

{{CommentController}}@index

* Return JSON: latest comments (desc), `paginate(10)`.

{{CommentController}}@store

* Validate: `body required|string`; create comment with `author_id`; return `204 No Content`.

---

## Step 6 — Frontend Pages (React + Inertia)

File: `resources/js/Pages/{{Module}}/Board.jsx`

Behavior

* Top filter bar: status dropdown, search input, sort dropdown, and Search button (Inertia GET with query).
* Grid/list of cards; each shows key fields (e.g., name, tags), badge for total {{recordsRelation}} count, badge with last record relative time, and link → `route('{{route-prefix}}.page', id)`.
* Pagination via Inertia GET preserving query string.

File: `resources/js/Pages/{{Module}}/Page.jsx`

Layout

* Header with primary entity context.
* Top section = form with fields: {{form-fields-list}}.
  * Fields as textareas/inputs with `@blur="save"`.
  * Autosave interval: every `{{autosave-interval-seconds}}` seconds; pending flag; skip when unchanged.
  * Show `Saving…` while in-flight, else `Saved`.
  * On first save, if no `recordId`, POST to `{{route-prefix}}.store`; then bind `recordId` and switch to PUT for subsequent saves.
  * Buttons: “Save Draft” and “Finalize”. Autofocus first field on mount.
* Attachments: simple `<input type="file" multiple>` rendered when `recordId` exists; POST to `.../attachments`; render as download links only.
* Timeline: cards with author, state badge, relative time; preview via `<details>`; comments lazy-loaded on expand; infinite scroll with partial reload (`only: ['records']`, `preserveScroll: true`).
* Admin override UI: if `can.admin` and finalized, show “Edit (Admin)” to enable editing via PUT.

Relative time helper (no extra libs): implement a small util converting timestamps to seconds/minutes/hours/days ago.

---

## Step 7 — Autosave Logic (exact)

Local state: `form`, `recordId=null`, `dirty=false`, `saving=false`.

On input: set `dirty=true`.

On blur: call `save()` if `dirty && !saving`.

Interval: `setInterval(save, {{autosave-interval-ms}})`; in `save()` skip if `!dirty || saving`.

Behavior:

* If `recordId == null`: `form.post(route('{{route-prefix}}.store', entity.id), { onSuccess: (page)=>{ recordId = page.props.newRecordId; dirty=false; saving=false; } })`
* Else: `form.put(route('{{route-prefix}}.update', recordId), { onFinish:()=>{ dirty=false; saving=false; } })`

---

## Step 8 — Search/Sort on Board

* Submit to `/{{module-path}}` with `?search=...&status=...&sort=...`.
* Backend filters: `status` where provided; `search` via `name LIKE %search%` and optionally `EXISTS` subquery matching form fields.
* Sort: by name (default), created_at desc, or latest related record using subquery `(select {{primary-entity-singular}}_id, max(created_at) as last from {{record-table}} group by {{primary-entity-singular}}_id)` ordered by `last desc`.
* Paginate 20 per page.

---

## Step 9 — Soft Delete & Restore

* destroy: soft-delete; hide for non-admins.
* Do not show restore UI for normal users; optional tiny admin-only restore action.

---

## Step 10 — Validation Rules (exact)

* Create/update: all form fields required with appropriate types (string/text). List: {{form-fields-list}}.
* Attachments: `files.* => 'file|max:{{max-attachment-kb}}'` ({{max-attachment-mb}} MB each), allow any mime by default.
* Comments: `body => 'required|string'`.

---

## Step 11 — Visual Details (must-have UX)

* Board cards: key attributes, badge: total records, badge: last record relative time, open link.
* Record page: autofocus first field, accurate autosave indicator, spinner during infinite scroll, comments collapsed by default and lazy-loaded, attachments as links only, timestamps in `id-ID` locale with 24h when absolute; relative for badges.

---

## Step 12 — Minimal Code Stubs (examples)

Migration example (`{{record-table}}`)

```php
Schema::create('{{record-table}}', function (Blueprint $t) {
  $t->id();
  $t->foreignId('{{primary-entity-singular}}_id')->constrained('{{primary-entity-table}}')->cascadeOnDelete();
  $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
  // form fields
  // e.g. $t->text('summary'); $t->text('details');
  $t->enum('state',[ 'draft','finalized' ])->default('draft');
  $t->timestamp('finalized_at')->nullable();
  $t->timestamps();
  $t->softDeletes();
  $t->index(['{{primary-entity-singular}}_id','created_at']);
});
```

Policy rule (update)

```php
public function update(User $u, {{Record}} $r){
  return $u->is_admin || ($r->author_id === $u->id && $r->state === 'draft');
}
```

Inertia infinite scroll (partial reload)

```js
router.get(route('{{route-prefix}}.page', entity.id), { page: next }, {
  only: ['records'], preserveState: true, preserveScroll: true, replace: true
})
```

Relative time helper (simple)

```js
function rel(t){
  const d = (Date.now() - new Date(t).getTime())/1000
  if (d < 60) return `${Math.floor(d)}s ago`
  if (d < 3600) return `${Math.floor(d/60)}m ago`
  if (d < 86400) return `${Math.floor(d/3600)}h ago`
  return `${Math.floor(d/86400)}d ago`
}
```

---

## Step 13 — Acceptance Criteria (verify all)

1. Visiting `/{{module-path}}` shows a new board with filters, search, sort, pagination.
2. Cards display total records and last record relative time.
3. Clicking a card opens `/{{module-path}}/{{primary-entity-route-segment}}/{id}` with form on top and timeline below.
4. Form autofocus works; autosave fires on blur + interval, with a visible indicator.
5. First save creates a draft and binds `recordId`; subsequent saves update the same record.
6. Finalize locks editing for non-admins; admin can edit finalized (override).
7. Timeline shows newest first, short preview → expand, status badge, author, relative time.
8. Infinite scroll loads next pages with a spinner and keeps scroll position.
9. Attachments upload (≤{{max-attachment-mb}} MB each), show as links, cannot be removed.
10. Comments fetch lazily on expand; newest-first; text-only posting works.
11. Soft delete hides the record for non-admins; admin can restore.
12. All times respect `{{app-timezone}}` with proper display.

---

## Step 14 — Quick Commands Recap

```bash
# migrations/models/policies/controllers
php artisan make:migration create_{{primary-entity-table}}_table
php artisan make:migration create_{{record-table}}_table
php artisan make:migration create_{{attachment-table}}_table
php artisan make:migration create_{{comment-table}}_table
php artisan migrate

php artisan make:model {{PrimaryEntity}}
php artisan make:model {{Record}}
php artisan make:model {{Attachment}}
php artisan make:model {{Comment}}

php artisan make:policy {{Record}}Policy --model={{Record}}

php artisan make:controller {{BoardController}}
php artisan make:controller {{PageController}}
php artisan make:controller {{RecordController}}
php artisan make:controller {{AttachmentController}}
php artisan make:controller {{CommentController}}
```

---

How to use this template

- Replace placeholders like `{{Module Name}}`, `{{module-path}}`, `{{PrimaryEntity}}`, `{{Record}}`, and `{{form-fields-list}}` with your domain terms.
- Pick sensible defaults: `{{autosave-interval-seconds}}` (e.g., 10), `{{max-attachment-mb}}` (e.g., 5), and compute `{{max-attachment-kb}} = {{max-attachment-mb}} * 1024`.
- Keep the module isolated unless integration is explicitly required.

## Vibe Kanban Scripts (project: osce)

- Setup script (osce): Installs deps, creates `webapp/database/database.sqlite`, then runs `php artisan migrate:fresh --force --seed`.
  - Recommended additions: copy `.env.example` to `.env` if missing, set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`, run `php artisan key:generate`, and consider `php artisan storage:link`.
- Dev script (osce): Runs Laravel server, queue listener, logs (pail), and Vite.
  - Recommended hardening: fall back gracefully if `pail` is not installed; prefer `queue:work --tries=1` for production-like behavior; keep the same port (`8001`) documented.
