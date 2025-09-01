# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Migration notice: The frontend is being migrated from Vue 3 to React using Inertia (React adapter) and the Vibe UI KIT. Legacy pages remain in Vue during the transition. Prefer React for new features; keep Vue pages stable until migrated.

## Users Rules
- please never edit vendor folder as it will be reinstalled on composer install
- never edit node_modules folder, it will be replaced on npm instlal


## 🎮 Gaming Design System - MANDATORY RULES

When working on ANY frontend component or page, you MUST follow the established gaming design system to maintain consistency across the application.

### ✅ ALWAYS USE These Components & Styles:

#### 1. **Cyber Borders** - NO RECTANGLES ALLOWED
```jsx
// Use cyber-border class for ALL containers, cards, buttons
<div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6">
```
**Rule:** NEVER use regular rectangles - always use angled corners via cyber-border class

#### 2. **Color Rotation Pattern** 
```jsx
const colors = [
  { bg: 'bg-gradient-to-br from-emerald-500/10 to-emerald-600/5', border: 'border-emerald-500/30', accent: 'text-emerald-400' },
  { bg: 'bg-gradient-to-br from-blue-500/10 to-blue-600/5', border: 'border-blue-500/30', accent: 'text-blue-400' },
  { bg: 'bg-gradient-to-br from-purple-500/10 to-purple-600/5', border: 'border-purple-500/30', accent: 'text-purple-400' }
];
const cardColor = colors[idx % colors.length];
```

#### 3. **Typography Hierarchy**
- **Page titles**: `text-2xl font-medium lowercase glow-text text-foreground`
- **Section headers**: `text-lg font-medium lowercase text-foreground font-mono`  
- **Body text**: `text-muted-foreground lowercase`
- **Status/labels**: `text-xs font-mono uppercase tracking-wider`

#### 4. **Interactive Elements**
```jsx
// Buttons must use cyber-button
<button className="cyber-button px-4 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide">

// Cards must have hover effects
className="hover:scale-[1.02] transition-all duration-300 group"

// Always include corner decorations
<div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
```

#### 5. **Status Indicators** - Always Include
```jsx
// Pulsing status dot + text
<div className="flex items-center gap-2">
  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
  <span className="text-xs text-emerald-500 font-mono uppercase">ONLINE</span>
</div>
```

#### 6. **Section Headers Pattern** - Use Consistently
```jsx
<div className="flex items-center gap-3 mb-4">
  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
  <h2 className="text-lg font-medium lowercase text-foreground font-mono">section title</h2>
  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
  <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
    <span>status info</span>
  </div>
</div>
```

#### 7. **Page Welcome Header** - Standard Pattern
```jsx
<div className="text-center space-y-4 relative">
  <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
  
  <div className="flex items-center justify-center gap-3">
    <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
    <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">SECTION LABEL</span>
    <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
  </div>
  
  <h1 className="text-2xl font-medium lowercase glow-text text-foreground">page title</h1>
</div>
```

### ❌ FORBIDDEN Styles:
- Plain rectangular borders
- Gray/boring card backgrounds without gradients
- Static elements without hover effects  
- Mixed case text (except uppercase labels)
- Hard-coded dark/light colors (use theme-aware classes)
- Missing decorative corner elements
- Instant transitions (always use duration-300)

### 🎯 Quality Checklist:
Before submitting any UI work, verify:
- [ ] All containers use `cyber-border` class
- [ ] Colors follow the emerald → blue → purple rotation
- [ ] Hover effects include `hover:scale-[1.02]` and `transition-all duration-300`
- [ ] Status indicators with pulsing dots are present
- [ ] Typography follows hierarchy (lowercase titles, mono technical text)
- [ ] Corner decorations on interactive elements
- [ ] Gradient backgrounds instead of flat colors
- [ ] Theme-aware text colors (`text-foreground`, `text-muted-foreground`)

**Reference:** See `/DESIGN_SYSTEM.md` for complete documentation and examples.

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

- **Always use Inertia React router/useForm for client interactions (no raw fetch):**
  - For navigations: use `<Link>` or `router.visit(url, opts)` from `@inertiajs/react`.
  - For mutations (POST/PUT/PATCH/DELETE): use `router.post/put/patch/delete` or `useForm` — do not use `fetch`/`axios` directly.
  - CSRF is automatically handled by Inertia; avoid manual CSRF header plumbing.
  - Only call plain JSON APIs directly when rendering JSON on-screen without navigation. Prefer `router.reload({ only: [...] })` for partial reloads.
  - Example (useForm):
    ```jsx
    import { useForm, router } from '@inertiajs/react'
    const { data, setData, post, processing, reset } = useForm({ osce_case_id: id })
    const start = () => post('/api/osce/sessions/start', { onSuccess: () => router.visit(`/osce/chat/${route().params.id}`) })
    ```
  - Example (router.post):
    ```jsx
    import { router } from '@inertiajs/react'
    router.post(`/api/osce/sessions/${sessionId}/assess`, { force: true }, { preserveScroll: true })
    ```
  - For file uploads use `useForm` with `transform` to send `FormData`.

- **Inertia first:** For new work, prefer the React adapter + Vibe UI KIT; keep legacy Vue pages stable until migrated.

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

## Vibe Kanban Guidelines (Concise)

- Trigger: Only create Vibe Kanban tasks when the user includes "cTask".
- Tooling: Use the Vibe Kanban MCP (agent/tool API), not the CLI, unless the user explicitly requests the CLI.
- Scope: Do not implement code when asked to "create task(s)"—only create/update the task in Kanban.
- Single PRD task: For new features, create exactly one PRD‑style task with an elaborative title and numbered objective actions.
- Traceability: Run TodoWrite first (non-empty `todos`), then perform the MCP action; include `project_id` and `task_id` on all task‑scoped operations; link `.claude/kanban/<feature-slug>.*` artifacts.
- Artifacts: Save the PRD at `.claude/kanban/<feature-slug>.prd.md` and link it in the task body.
- Defaults: Asia/Jakarta TZ, autosave 10s, 5 MB attachments, local storage, SQLite (dev), Inertia SPA.

### PRD contents (must include)
1) Background & rationale (why now; reference files/routes/models by path).
2) Objectives & non‑goals.
3) Architecture & data model (migrations, models, relationships, indexes, constraints).
4) API/Routes & controllers (methods, URIs, names; validation & auth rules).
5) Frontend (Vue + Inertia) pages/components, state, autosave/infinite scroll, UX details.
6) Tooling usage (Laravel Boost MCP: routes, schema, tinker, docs search, logs) with example calls.
7) Commands (Artisan/npm) to scaffold/migrate/build/test.
8) Code examples (minimal PHP/Vue snippets for patterns—not full impls).
9) Validation, errors, permissions (rules, common failures, logging/retries).
10) Acceptance criteria (exhaustive, testable; include edge cases & permission matrix).
11) Test plan (Pest/PHPUnit + manual/inertia behaviors; data setup/teardown).
12) Rollout & risks (migration order, data issues, fallback/rollback).

### Titles & objective actions
- Titles must be elaborative; objective actions must be precise with commands, file paths, data rules, and acceptance checks.

### MCP parameters & recovery
- Always include `project_id` and `task_id` for task actions.
- On `InputValidationError` or missing IDs, reissue with required fields; log attempted action and IDs in `.claude/kanban/<feature-slug>.*` when applicable.
- Before any task‑scoped action, create/update a Todo via TodoWrite describing the operation and referencing the IDs.

### Tooling source of truth
- When the user says “create function using vibe-kanban”, use MCP (not CLI) for creating/updating/deleting tasks.

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
