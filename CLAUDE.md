# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel project with a Vue.js frontend using Inertia.js. The main application code is located in the `webapp` directory. It uses TypeScript, Tailwind CSS, and shadcn-vue for UI components.

## Technology Stack

- **Backend**: Laravel
- **Frontend**: Vue.js with Inertia.js
- **Styling**: Tailwind CSS
- **UI Components**: shadcn-vue
- **Language**: TypeScript (frontend), PHP (backend)

## Common Commands

All commands should be run from the `webapp` directory.

- **`npm run dev`**: Starts the Vite development server for frontend assets.
- **`npm run build`**: Compiles and bundles frontend assets for production.
- **`npm run lint`**: Lints the codebase using ESLint and attempts to fix issues.
- **`npm run format`**: Formats the code using Prettier.
- **`npm run format:check`**: Checks the formatting without writing changes.
- **`composer install`**: Installs PHP dependencies.
- **`php artisan test`**: Runs the PHPUnit tests.

## Architecture

The application follows a standard Laravel structure with Vue.js and Inertia for the frontend.

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
  - Always check todos to ensure Inertia architecture is used first before considering alternatives

## **Component & Page Management**

- **ShadCN Vue is already installed** - Don't reinstall it. Always use existing shadcn components whenever possible. If asked to install a new component, install only that specific component.

- **New Page Creation Process:**
  1. Create Vue page in Resources/js/Pages/
  2. Add page to AppSidebar.vue navigation
  3. Create corresponding Laravel Controller
  4. Add routes in web.php with proper Inertia responses

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
