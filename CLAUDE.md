# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Migration notice: The frontend is being migrated from Vue 3 to React using Inertia (React adapter) and the Vibe UI KIT. Legacy pages remain in Vue during the transition. Prefer React for new features; keep Vue pages stable until migrated.

## Project Overview

This is a Laravel project with an Inertia-powered SPA frontend. The main application code is located in the `webapp` directory. We currently have a mix of Vue (legacy) and React (new) pages. Styling uses Tailwind; UI components are shadcn-vue for legacy and Vibe UI KIT for new React pages.

## Technology Stack

- **Backend**: Laravel
- **Frontend**: Inertia SPA — Vue (legacy), React (in progress) with Vibe UI KIT
- **Styling**: Tailwind CSS
- **UI Components**: shadcn-vue (legacy), Vibe UI KIT (React)
- **Language**: TypeScript (frontend), PHP (backend)

## Common Commands

All commands should be run from the `webapp` directory.

- `npm run dev`: Starts the Vite development server for frontend assets.
- `npm run build`: Compiles and bundles frontend assets for production.
- `npm run lint`: Lints the codebase using ESLint and attempts to fix issues.
- `npm run format`: Formats the code using Prettier.
- `npm run format:check`: Checks the formatting without writing changes.
- `composer install`: Installs PHP dependencies.
- `php artisan test`: Runs the PHPUnit tests.

## Architecture

The application follows a standard Laravel structure with Inertia for the frontend. Use React + Inertia for new work; Vue + Inertia remains for legacy pages until migrated.

### Models

Located in `webapp/app/Models`, these Eloquent models interact with the database.

- `User`: Manages user data and authentication.
- `OsceCase`: Represents an OSCE case scenario.
- `OsceSession`: Tracks a user's session for a specific OSCE case.
- `OsceChatMessage`: Stores messages exchanged during an OSCE chat session.
- `MedicalTest`: Defines available medical tests that can be ordered.
- `SessionOrderedTest`: Links ordered tests to an OSCE session.
- `Post`: Represents a forum post.
- `Comment`: Represents a comment on a forum post.
- `Notification`: Manages user notifications.
- `PostInteraction`: Tracks user interactions with posts (e.g., likes).
- `SessionExamination`: Logs examinations performed during a session.

### Controllers

Located in `webapp/app/Http/Controllers`, these handle the application's business logic.

- `LandingController`: Manages the home page.
- `OsceController`: Handles core OSCE functionality, including starting sessions, ordering tests, and managing timers.
- `OsceChatController`: Manages the real-time chat functionality for OSCE sessions.
- `MedicalTestController`: Provides an API for searching and categorizing medical tests.
- `PostController`: Manages CRUD operations for forum posts.
- `CommentController`: Manages CRUD operations for comments on posts.
- `MCQController`: Handles the multiple-choice question demo.
- `Settings/ProfileController`: Manages user profile settings.

### Routing

Route files are located in `webapp/routes`.

- `web.php`: Defines the main application routes, including the dashboard, OSCE, and forum. Most routes are protected by `auth` middleware.
- `auth.php`: Handles user authentication (login, logout) via WorkOS.
- `settings.php`: Defines routes for user settings, such as profile and appearance.


[byterover-mcp]

# important 
always use byterover-retrieve-knowledge tool to get the related context before any tasks 
always use byterover-store-knowledge to store all the critical informations after sucessful tasks

---
description: General development rules for Laravel + Inertia.js + Vue.js project with documentation and tooling preferences
globs: **/*
alwaysApply: true
---

## **Core Architecture & Development Principles**

- **Always use Laravel Boost MCP tools when possible** for Laravel-specific operations including:
  - Database queries and schema inspection
  - Application configuration retrieval
  - Route analysis and Artisan commands
  - Error logging and debugging
  - Documentation searches

- **Always document critical information using ByteRover** after completing tasks:
  - Store implementation patterns and code solutions
  - Document error resolutions and debugging steps
  - Capture API integration details and configurations
  - Record testing patterns and best practices

- **Always use Inertia.js Architecture whenever possible:**
  - Use `Inertia.get()`, `Inertia.post()`, `Inertia.put()`, `Inertia.delete()` instead of regular APIs
  - Leverage `Inertia.visit()` for navigation
  - Use `router.visit()` with proper options for form submissions
  - For new work, prefer the React adapter and Vibe UI KIT; for existing Vue pages, stay within the current Vue structure until migrated.

## **Component & Page Management**

- **Vibe UI KIT (React) is the default for new UI**. shadcn-vue is installed for legacy Vue pages — do not rework unless migrating the page fully to React.

- **New Page Creation Process:**
  1. Create React page in `resources/js/Pages/` (or relevant feature dir) using Inertia React.
  2. Add navigation entry to the appropriate layout/sidebar.
  3. Create corresponding Laravel Controller.
  4. Add routes in `web.php` with proper Inertia responses.

- **Error Handling:**
  - Use Browser Logs (Laravel Boost MCP Tools) to check latest errors
  - Confirm specific error before fixing (check last 2 entries only)
  - Don't assume - always verify the exact error first

## **Single Page Application Guidelines**

- **Minimize web reloading** - Always use Inertia.js SPA principles
- **Form Submissions:** Use `router.post()` with proper success/error handling
- **Navigation:** Use `Inertia.visit()` or `Link` component instead of traditional links
- **Data Fetching:** Prefer server-side data loading through Inertia props over client-side API calls

## **Development Workflow**

- **Before implementing any solution:** Check if Inertia.js architecture can be used
- **For API operations:** Default to Inertia methods (`Inertia.fetch`, `Inertia.post`) over traditional REST APIs
- **Documentation:** Always store successful implementation patterns in ByteRover knowledge base
- **Laravel Integration:** Leverage Laravel Boost MCP tools for project analysis and debugging
- **If unsure, research first:** If you are not sure what to do, use search or fetch the documentation before proceeding with an implementation.

## **AI Prompt Creation Guidelines**

- **When creating AI prompts for implementation tasks:**
  - Always provide conversation context explaining WHY changes are needed
  - Include relevant existing codebase functions/models/structure as context
  - Reference specific files, methods, and database schemas that exist
  - Keep code examples concise - show structure, not full implementations
  - Explain the educational/technical problem being solved
  - Ensure next AI understands the reasoning behind architectural decisions

## Vibe Kanban Guidelines

When creating a task in Vibe Kanban, always provide a detailed description with the following context:
- **Project folder context**: e.g., `Project folder: /home/bintangputra/osc`.
- **Reasoning**: Explain why the task needs to be done.
- **Relevant Code**: Point to relevant controllers, models, or components (e.g., `The relevant controller is OsceController.php`).

IMPORTANT! -> Only invoke creating task using Vibe-Kanban MCP when "cTask" string is invoked by the user

### Vibe Kanban Tooling Source of Truth

- Interpretation: When the user says “create function using vibe-kanban”, use the Vibe Kanban MCP (agent/tool API), not the `npx vibe-kanban` CLI.
- Do not invoke external CLIs for Vibe Kanban unless the user explicitly requests the CLI. Prefer MCP calls for creating, updating, or deleting tasks.

### Vibe Kanban Role and Agent Responsibilities

- Vibe Kanban is the todo app and agent system. It owns task creation, assignment, and execution tracking.
- When the user says “create task(s)” in Vibe Kanban, do not implement code or make repo changes. Only create the requested tasks via the Vibe Kanban MCP.
- Single‑agent model: Create one PRD‑style task that fully specifies the work. Implementation and testing happen only if the user asks to execute outside Kanban.
- Always follow these when creating tasks:
  - Use MCP (not CLI) and include `project_id` and `task_id` for task-scoped operations.
  - Use `TodoWrite` first (with a non-empty `todos` array) to capture intent/context, then perform the MCP task action.
  - Link artifacts (e.g., `.claude/kanban/<feature-slug>.*.md`) in task descriptions for traceability.
  - Do not proceed to implementation unless the user explicitly asks to execute outside the Kanban workflow.

### Vibe Kanban — Elaborative Titles and Objective Actions (Required)

When creating tasks, always write both the task title and the objective actions as a detailed, self-contained prompt that a developer can execute without guessing. Avoid terse labels. Include scope, rationale, concrete deliverables, file paths, commands, data rules, and acceptance checks.

- Task title standard (must be elaborative):
  - Good: "Diagnosis: Build a standalone SOAP module under /soap (Laravel + Vue 3 + Inertia) — define DB schema (patients, soap_notes, attachments, comments), policies (admin override), routes, controllers, and Vue pages with autosave and infinite scroll; isolate from OSCE; SQL LIKE search; acceptance criteria listed."
  - Bad: "Diagnosis: SOAP module".

- Objective actions standard (must be enumerated and explicit):
  - Include numbered steps with exact commands (e.g., `php artisan make:migration ...`), file paths (e.g., `webapp/app/Models/SoapNote.php`), route names, Vue component filenames, validation rules, and UX states.
  - End the description with explicit, testable acceptance criteria and edge cases.

- Cross-linking and slug:
  - Define a single kebab-case `<feature-slug>` in Diagnosis and use it across `.prompt.md`, `.implementation.md`, `.tests.md`.
  - Link these files in each task body for traceability.

- Consistency defaults (unless overridden by the task): Asia/Jakarta timezone, autosave every 10s, 5 MB attachment limit, local storage, SQLite for dev, Inertia SPA conventions.

## Vibe Kanban Workflow for New Functions (Single‑Task, PRD‑Style)

When creating a new function or feature, use a single task that acts as a comprehensive PRD. This one task must contain everything needed for a developer to implement and test the feature without ambiguity.

### Task: Feature PRD (Single Agent)
-  Objective: Produce a PRD‑level, elaborative prompt describing the feature in depth, including rationale, architecture, exact changes, commands, code examples, tool usage, and acceptance criteria.
-  Contents (must include all of the following):
   1) Background and Rationale: why it’s needed now; reference existing files by path and related routes/models/controllers.
   2) Objectives and Non‑Goals: precise goals; what’s out of scope.
   3) Architecture and Data Model: migrations, Eloquent models and relationships, indexes, constraints.
   4) API/Routes and Controllers: route table (methods, URIs, names), controller actions with validation and authorization rules.
   5) Frontend (Vue + Inertia): pages/components, state, autosave/infinite scroll behavior, UX details.
   6) Tooling Usage: how to use Laravel Boost MCP tools at each step (list tools, when/why to call them: routes, schema, tinker, docs search, logs); include example calls and expected outputs.
   7) Commands: exact Artisan and npm commands to run (scaffolding, migrations, building, testing).
   8) Code Examples: minimal but precise PHP (migrations/models/policies/controllers) and Vue snippets to illustrate patterns and function usage; avoid full implementations unless essential.
   9) Validation, Errors, and Permissions: rules, common failures, logging and retries strategy.
   10) Acceptance Criteria: exhaustive, testable checks including UX states, edge cases, and permission matrix.
   11) Test Plan: what to cover with Pest/PHPUnit and manual/inertia behaviors; data setup and teardown.
   12) Rollout and Risks: migration order, potential data issues, fallback/rollback notes.
### Task Title (must be elaborative):
   - Template: "PRD: <feature-name> — goals, architecture (migrations/models/routes/controllers/components), tool usage (Laravel Boost MCP), commands, code examples, validation/permissions, acceptance criteria, and test plan."
   - Example: "PRD: Standalone SOAP module under /soap (Laravel + Vue 3 + Inertia) — define patients/soap_notes schema, policies with admin override, routes/controllers, Board.vue/Page.vue with autosave+infinite scroll; use SQL LIKE search; include Laravel Boost MCP tool usage (routes/schema/tinker/docs); provide code examples; acceptance criteria and test plan included."

### Filename Convention and Task Enforcement

- Always create exactly one Vibe Kanban task for a new function/feature: a PRD‑style prompt task (single agent).
- Define a kebab‑case `<feature-slug>` and save the PRD as `.claude/kanban/<feature-slug>.prd.md`.
- The task description must include all PRD sections above and link to `.claude/kanban/<feature-slug>.prd.md`.
- If the user later requests execution, reference this PRD for implementation and testing; track outcomes in the same PRD or in follow‑up notes as requested by the user.

### Vibe Kanban MCP Parameter Requirements

- Always include `project_id` and `task_id` when invoking any task-scoped Vibe Kanban MCP operation (e.g., delete, update, move, comment).
- Do not issue Vibe Kanban MCP calls that act on tasks without both identifiers present.
- Example parameters (pseudocode): `{ project_id: "<proj-id>", task_id: "<task-id>", action: "delete" }`.
- Error recovery:
  - If you see `InputValidationError` or MCP `-32602 missing field project_id/task_id`, stop and reissue the request including both fields.
  - Log the attempted action and the IDs used in `<feature-slug>.implementation.md` or `<feature-slug>.tests.md` when applicable.

- Additionally, before any task-scoped Vibe Kanban action, create/update a Todo via `TodoWrite` that describes the intended operation and references the `project_id`/`task_id` for traceability.
  - Example (pseudocode): `TodoWrite({ todos: ["VK delete task <task-id> in <project-id>"], context: "cleanup redundant tasks" })`.

---
alwaysApply: true
---
# Laravel-Boost MCP Integration

When the user asks anything related to Laravel development, always use the Laravel Boost MCP tools to provide assistance. This includes:

- Laravel project structure and setup
- Eloquent models and relationships
- Controllers and routing
- Middleware and authentication
- Database migrations and seeders
- Artisan commands
- Blade templates and views
- Laravel configuration
- Package management with Composer
- Testing in Laravel
- Performance optimization
- Debugging and troubleshooting

Use the Laravel Boost MCP tools to analyze the project structure, generate code, provide best practices, and offer Laravel-specific solutions.

Available MCP Tools & Their Purposes
 • Application Info: Read PHP & Laravel versions, database engine, list of ecosystem packages with versions, and Eloquent models.
 • Browser Logs: Read logs and errors from the browser.
 • Database Connections: Inspect available database connections, including the default connection.
 • Database Query: Execute a query against the database.
 • Database Schema: Read the database schema.
 • Get Absolute URL: Convert relative path URIs to absolute URLs for valid agent-generated links.
 • Get Config: Retrieve values from configuration files using dot notation.
 • Last Error: Read the last error from application log files.
 • List Artisan Commands: Inspect the available Artisan commands.
 • List Available Config Keys: View available configuration keys.
 • List Available Env Vars: View available environment variable keys.
 • List Routes: Inspect application routes.
 • Read Log Entries: Read the last N log entries.
 • Report Feedback: Share Boost & Laravel AI feedback with the team.
 • Search Docs: Query the Laravel documentation API for installed packages.
 • Tinker: Execute arbitrary code within the application context.

How to Use in Cursor
 • Start the MCP server using the configuration above.
 • Use Cursor’s AI agent to leverage these tools for enhanced, context-aware Laravel coding.
 • The AI agent can access project structure, run database queries, inspect logs, and search documentation, resulting in more accurate and framework-specific code generation.

---
description: Guidelines for continuously improving Cursor rules based on emerging code patterns and best practices.
globs: **/*
alwaysApply: true
---

- **Rule Improvement Triggers:**
  - New code patterns not covered by existing rules
  - Repeated similar implementations across files
  - Common error patterns that could be prevented
  - New libraries or tools being used consistently
  - Emerging best practices in the codebase

- **Analysis Process:**
  - Compare new code with existing rules
  - Identify patterns that should be standardized
  - Look for references to external documentation
  - Check for consistent error handling patterns
  - Monitor test patterns and coverage

- **Rule Updates:**
  - **Add New Rules When:**
    - A new technology/pattern is used in 3+ files
    - Common bugs could be prevented by a rule
    - Code reviews repeatedly mention the same feedback
    - New security or performance patterns emerge

  - **Modify Existing Rules When:**
    - Better examples exist in the codebase
    - Additional edge cases are discovered
    - Related rules have been updated
    - Implementation details have changed

- **Example Pattern Recognition:**
  ```typescript
  // If you see repeated patterns like:
  const data = await prisma.user.findMany({
    select: { id: true, email: true },
    where: { status: 'ACTIVE' }
  });
  
  // Consider adding to [prisma.mdc](mdc:.cursor/rules/prisma.mdc):
  // - Standard select fields
  // - Common where conditions
  // - Performance optimization patterns
  ```

- **Rule Quality Checks:**
  - Rules should be actionable and specific
  - Examples should come from actual code
  - References should be up to date
  - Patterns should be consistently enforced

- **Continuous Improvement:**
  - Monitor code review comments
  - Track common development questions
  - Update rules after major refactors
  - Add links to relevant documentation
  - Cross-reference related rules

- **Rule Deprecation:**
  - Mark outdated patterns as deprecated
  - Remove rules that no longer apply
  - Update references to deprecated rules
  - Document migration paths for old patterns

- **Documentation Updates:**
  - Keep examples synchronized with code
  - Update references to external docs
  - Maintain links between related rules
  - Document breaking changes
Follow [cursor_rules.mdc](mdc:.cursor/rules/cursor_rules.mdc) for proper rule formatting and structure.

---
description: A protocol for ensuring robust tool usage and recovering from errors gracefully.
globs: **/*
alwaysApply: true
---

## AI Tool Usage and Recovery Protocol

To ensure smooth and error-free operation, follow these directives when using tools to modify files or manage tasks.

- **Prioritize Atomic Operations**: When a task requires multiple edits, especially across different logical blocks or files, favor multiple, sequential `Edit` calls over a single, complex `MultiEdit` call. This approach isolates failures and makes debugging straightforward.

- **Proactive Self-Correction**: Before executing any tool, perform a quick internal check to ensure all required parameters are present and correctly formatted. If a required parameter is missing (e.g., `todos` for `TodoWrite`), correct the call *before* execution.

- **Systematic Error Handling**: If a tool call fails with an `InputValidationError`:
  1.  Do not retry the exact same failed command.
  2.  Analyze the error message to identify the specific missing or incorrect parameter.
  3.  Construct a new, valid tool call with the corrected parameters.
  4.  Execute the corrected command.

- **Graceful Degradation**: If a complex tool fails for reasons beyond a simple input error, switch to a simpler, more reliable method. For example, if `MultiEdit` fails unexpectedly, break the task into smaller pieces and handle each with a separate `Edit` call. This ensures progress is not blocked by a single complex failure.

### TodoWrite Requirement (All Tasks)

- For any activity (including Vibe Kanban operations), always create a Todo first using `TodoWrite` before performing any action.
- Always provide the required `todos` parameter to `TodoWrite`. Do not proceed if `todos` is missing or empty.
- After creating the Todo, proceed with the work and keep the Todo updated as progress is made.
- Examples (pseudocode):
  - General: `TodoWrite({ todos: ["Migrate DB", "Update docs"], context: "why and where" })`
  - Vibe Kanban: `TodoWrite({ todos: ["VK create task: Diagnosis for blog-feature"], context: "project=<proj-id>; link to .claude/kanban/blog-feature.prompt.md" })`
- Error recovery:
  - If `TodoWrite` fails with `The required parameter 'todos' is missing`, reissue it with a non-empty `todos` array.
  - Record created Todo IDs and link them in artifacts (implementation/test notes) for traceability.

## AI Prompt Template — Standalone Feature Module (Laravel + Vue 3 + Inertia 2.0)

Use this when asking for a laser-detailed, do-this-exactly prompt to scaffold a brand-new, isolated module. Replace all placeholders like `{{placeholder}}` before pasting into your AI/dev tool.

---

# PROMPT: Build a Standalone {{Module Name}} Module (Laravel + Vue 3 + Inertia 2.0)

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

* Stack: Laravel, Vue 3, Inertia.js 2.0, dev DB SQLite, storage local.
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

## Step 6 — Frontend Pages (Vue + Inertia)

File: `resources/js/Pages/{{Module}}/Board.vue`

Behavior

* Top filter bar: status dropdown, search input, sort dropdown, and Search button (Inertia GET with query).
* Grid/list of cards; each shows key fields (e.g., name, tags), badge for total {{recordsRelation}} count, badge with last record relative time, and link → `route('{{route-prefix}}.page', id)`.
* Pagination via Inertia GET preserving query string.

File: `resources/js/Pages/{{Module}}/Page.vue`

Layout

* Header with primary entity context.
* Top section = form with fields: {{form-fields-list}}.
  * Fields as textareas/inputs with `@blur=\"save\"`.
  * Autosave interval: every `{{autosave-interval-seconds}}` seconds; pending flag; skip when unchanged.
  * Show `Saving…` while in-flight, else `Saved`.
  * On first save, if no `recordId`, POST to `{{route-prefix}}.store`; then bind `recordId` and switch to PUT for subsequent saves.
  * Buttons: “Save Draft” and “Finalize”. Autofocus first field on mount.
* Attachments: simple `<input type=\"file\" multiple>` rendered when `recordId` exists; POST to `.../attachments`; render as download links only.
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

---

## AI Prompt Template — Fix or Modify an Existing Feature

Use this when asking for a detailed prompt to fix or modify an existing feature.

# PROMPT: Fix or Modify an Existing Feature: {{Feature Name}}

## 1. Context & Location

*   **Feature being modified:** A brief, one-sentence description of the feature and its purpose.
*   **Key Files & Code Location:** Pinpoint the exact files and functions to be changed.
    *   Controller/Method: `webapp/app/Http/Controllers/{{ControllerName}}.php` at `function {{methodName}}()`
    *   Model: `webapp/app/Models/{{ModelName}}.php`
    *   Vue Component: `webapp/resources/js/Pages/{{ComponentName}}.vue`
    *   Route: `webapp/routes/{{routeName}}.php`

---

## 2. Problem Description / Modification Goal

*   **Current Behavior:** Describe what the system currently does. If it's a bug, explain the incorrect behavior with steps to reproduce.
*   **Expected Behavior:** Describe what the system *should* do after the change. Be specific and clear.
*   **Reason for Change:** Explain why this modification is necessary (e.g., "Bug fix for incorrect calculation," "Enhancement to support new user roles").

---

## 3. Implementation Details

*   **Current Implementation:** Paste the relevant code snippet of the function/component that needs to be modified.

    ```php
    // Paste the existing function code here, for example:
    public function store(Request $request)
    {
        // ... current logic ...
    }
    ```

*   **Specified Approach & Required Changes:** Describe the technical strategy and list the exact changes needed.
    *   **Approach:** *Example: "Refactor the validation logic into a dedicated Form Request class to keep the controller clean. Add a new service to handle the file upload."*
    *   **Changes:**
        1.  *Example: "Create a new `StorePostRequest` Form Request."*
        2.  *Example: "In `PostController@store`, replace the inline validation with `StorePostRequest`."*
        3.  *Example: "Add a `try-catch` block to handle potential `FileUploadException`."*

---

## 4. Constraints / Non-Goals

*   **What NOT to change:** Clearly state any parts of the application that must remain untouched.
    *   *Example:* "Do not alter the `posts` table schema." or "The frontend UI must not be changed."

---

## 5. Acceptance Criteria (verify all)

*   Provide a checklist of testable outcomes to confirm the fix or modification is successful.
    *   *Example 1:* "1. Submitting the form with a missing `title` field returns a validation error."
    *   *Example 2:* "2. A successful form submission creates a new record in the `posts` table."
    *   *Example 3:* "3. Uploading a file larger than 5MB results in a specific error message."

---

## How to use this template

-   Replace all placeholders like `{{Feature Name}}` with specific details about the task.
-   Provide the *actual code* for the "Current Implementation" to give the AI full context.
-   Be as explicit as possible in the "Specified Approach & Required Changes" section.

