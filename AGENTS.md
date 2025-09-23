# AGENTS.md

Ringkasan aturan untuk AI agents dan kontributor di repo ini.

Migration notice: Frontend sedang migrasi dari Vue 3 ke React menggunakan Inertia + Vibe UI KIT. Fitur baru → React; halaman Vue tetap stabil sampai dimigrasi.

## 1) Aturan Inti (Do / Don't / Pengecualian)

Do
- Gunakan React + Inertia untuk fitur baru; Vue hanya untuk halaman legacy.
- Ikuti Minimal Design System: gunakan komponen/pola "clean" (clean-card, clean-button), tipografi dan spacing konsisten, warna via CSS variables.
- Interaksi client: gunakan @inertiajs/react (router.* atau useForm) untuk navigasi dan mutasi.
- CSS: pakai variabel tema (—*), bukan hard-coded colors.
- Struktur Laravel standar; update controller/route/model sesuai konvensi proyek.

Don't
- Jangan edit vendor/ dan node_modules/.
- Jangan pakai fetch/axios langsung untuk navigasi/mutasi (lihat pengecualian di bawah).
- Jangan pakai kelas/gaya "cyber-*" atau efek dekoratif berlebihan.
- Jangan menumpuk shadow/glow di atas clean-card tanpa alasan kuat.

Pengecualian
- Boleh panggil JSON API langsung hanya bila menampilkan JSON di layar tanpa navigasi (render data lokal saja).

## 2) Frontend Minimal Design System (Ringkas)
- Filosofi UI: SaaS-like, elegan, modern, dan mengedepankan user experience (UX-first).
- Komponen/UI: Vibe UI KIT untuk React (default). shadcn-vue hanya untuk legacy Vue (jangan dirombak kecuali migrasi penuh).
- Pola layout: space-y-6 (vertikal), grid gap-4, flex gap-3. Header section: border-b + pb-3 + mb-6.
- Card: gunakan `clean-card` (sudah ada hover lift). Hindari menumpuk shadow lain.
- Tipografi:
  - Page title: `text-2xl font-semibold text-foreground`
  - Section: `text-lg font-medium`
  - Body: `text-muted-foreground`
  - Small: `text-sm text-muted-foreground`
- Detail lengkap: lihat DESIGN_SYSTEM.md.

## 3) Interaksi Client (Inertia-first)
- Navigasi: `<Link>`, `router.visit(url, opts)`.
- Mutasi: `router.post/put/patch/delete` atau `useForm`.
- Upload: gunakan `useForm` + `transform` → `FormData`.
- Partial reload: `router.reload({ only: [...] })``.
- CSRF ditangani Inertia (hindari plumbing manual).

## 4) Backend (Laravel)
- Tambahkan controller di `app/Http/Controllers` + route di `routes/web.php`.
- Model di `app/Models` dengan relasi Eloquent standar.
- Testing: prioritaskan PHPUnit/Pest untuk logic baru.

## 5) Tooling Policy (Prioritas dan Peran)

### Tooling Decision Matrix

**Purpose:**
- **Gemini CLI**: Understand the codebase and features (functions/components), trace data flow, and update documentation.md with context.
- **Laravel Boost MCP**: Inspect/execute Laravel runtime: routes, Artisan commands, DB/schema, logs/errors, config, Tinker.

**Order of operations:**
1) Check @documentation.md (use if up to date).
2) Use Gemini CLI (targeted) to understand code paths; update documentation.md with context (avoid full reindex).
3) Use Laravel Boost MCP to run/inspect (artisan, routes, schema, logs) as needed.

**Overlap policy:**
- If the goal is to understand the codebase/feature: use Gemini CLI (targeted analysis) first.
- If the goal is to execute or inspect Laravel runtime (artisan/routes/db/logs): use Laravel Boost MCP.
- When both could apply: start with Gemini to map files/flow, then validate or execute with Laravel Boost MCP if runtime confirmation or Artisan actions are needed.

**Retry & fallback:**
- Gemini CLI: retry up to 3x (then simplify scope) before manual file reading.
- Laravel Boost MCP: on failure, inspect error/logs and correct parameters; avoid repeating the same failing call.

### Using Gemini CLI for Targeted Feature Analysis

**PRIORITIZE SPECIFIC FEATURE ANALYSIS over full codebase analysis**

- Please use targeted analysis before implementing task - focus on SPECIFIC functions/features, NOT entire codebase
- ALWAYS check if @documentation.md existed and is uptodate, if it exists, please refer using that and don't reindex the codebase, but if its not uptodate then use Gemini CLI first when you try to understand this codebase and then update the documentation.md
- Everytime you add a new knowledge, please tell GEMINI.CLI to update the documentation.md, but give context, so gemini_cli doesnt reindex all the codebase again (this will cause to eat time)
- If Gemini CLI fails, retry up to 3 times before falling back to other methods

**Targeted Analysis Strategy**
**ALWAYS prioritize specific feature analysis:**
1. **Identify specific function/feature** you need to understand or implement
2. **Target relevant files/directories** only for that feature
3. **Use detailed, specific prompts** with clear context and requirements
4. **Avoid full codebase analysis** unless absolutely necessary

**Retry Strategy**
If a Gemini CLI command fails:
1. First attempt: Try the original command
2. Second attempt: Retry the same command (network/API issues)
3. Third attempt: Retry with simplified prompt or smaller scope
4. Fourth attempt: Fall back to manual file reading only if all Gemini attempts fail

### File and Directory Inclusion Syntax

Use the `@` syntax to include SPECIFIC files and directories relevant to your target feature. The paths should be relative to WHERE you run the gemini command.

**FOCUS ON RELEVANT FILES ONLY - NOT ENTIRE CODEBASE**

#### Examples:

**Target specific feature files:**
```bash
gemini -p "@src/auth/ @middleware/auth.js I need to understand how JWT authentication works in this app. Please explain: 1) How tokens are generated and validated, 2) What middleware is used, 3) How protected routes work, 4) Any refresh token logic"
```

**Analyze specific component:**
```bash
gemini -p "@components/UserProfile.jsx @hooks/useUser.js I'm working on user profile functionality. Please analyze: 1) How user data is fetched and managed, 2) What props/state are used, 3) How profile updates work, 4) Any validation or error handling"
```

**Target API endpoints:**
```bash
gemini -p "@routes/api/users.js @controllers/userController.js @models/User.js I need to understand the user management API. Please explain: 1) All available endpoints, 2) Request/response formats, 3) Validation rules, 4) Database operations"
```

**Focus on specific functionality:**
```bash
gemini -p "@src/payment/ @utils/stripe.js I'm implementing payment processing. Please analyze: 1) How payments are handled, 2) What payment methods are supported, 3) Error handling for failed payments, 4) Webhook implementation"
```

### Detailed Prompt Guidelines

**Always provide specific context and requirements:**

#### ✅ GOOD - Detailed and Specific:
```bash
gemini -p "@src/chat/ @components/ChatRoom.jsx I'm implementing real-time chat functionality. Please analyze and explain: 1) How WebSocket connections are established and maintained, 2) Message format and data structure, 3) How typing indicators work, 4) Room joining/leaving logic, 5) Any authentication for chat access"
```

#### ❌ BAD - Vague and General:
```bash
gemini -p "@src/ Analyze the codebase"
```

#### ✅ GOOD - Focused Feature Analysis:
```bash
gemini -p "@components/DataTable.jsx @hooks/useTableData.js I need to add sorting and filtering to the data table. Please explain: 1) Current data fetching logic, 2) How table state is managed, 3) Existing sorting/filtering if any, 4) Prop structure and data flow, 5) Best approach to add new sorting columns"
```

#### ✅ GOOD - Implementation Verification:
```bash
gemini -p "@src/auth/ @middleware/ I need to verify authentication security. Please check: 1) Are passwords properly hashed with salt?, 2) Is rate limiting implemented for login attempts?, 3) Are JWTs properly validated on protected routes?, 4) Any CSRF protection?, 5) Session management approach"
```

### When to Use Gemini CLI

**PRIORITIZE for specific feature analysis:**
- Understanding SPECIFIC functions, components, or features
- Analyzing targeted file groups related to one functionality
- Verifying specific implementation patterns
- Getting context for implementing similar features
- Understanding data flow for particular features

**Use ONLY when necessary for broader analysis:**
- Creating comprehensive documentation (after specific analysis)
- Understanding overall architecture (rare cases)
- Project structure overview (initial setup only)

### Full Codebase Analysis (When Necessary)

**Use full codebase analysis ONLY when:**
- You don't know where specific features are located
- You need to discover existing implementations
- Initial project exploration
- Finding related files for unknown features

#### Discovery and Exploration Examples:

**Discover authentication system:**
```bash
gemini -a -p "Please explain how authentication works in this app. I need to understand: 1) What authentication method is used (JWT, sessions, etc), 2) Where are auth-related files located, 3) How login/logout works, 4) How protected routes are handled, 5) Any middleware or guards used"
```

**Two-Phase Approach (Recommended):**

**Phase 1 - Discovery:**
```bash
gemini -a -p "I need to implement real-time notifications. Please help me discover: 1) Are there existing notification systems?, 2) What WebSocket or SSE implementations exist?, 3) Where are notification-related files located?, 4) How are notifications stored/managed?, 5) Any existing UI components for notifications"
```

**Phase 2 - Targeted Analysis:**
```bash
gemini -p "@src/notifications/ @components/NotificationCenter.jsx @api/notifications.js Now I found the notification files. Please analyze in detail: 1) How notifications are created and sent, 2) WebSocket connection handling, 3) Notification persistence and retrieval, 4) UI components and their props, 5) Any real-time update mechanisms"
```

### Laravel Boost MCP Integration

When working on Laravel, use Laravel Boost MCP for runtime introspection and Artisan operations; for understanding the codebase or feature behavior, prefer Gemini CLI.

**Always use Laravel Boost MCP tools when possible for Laravel-specific operations including:**
- Database queries and schema inspection
- Application configuration retrieval
- Route analysis and Artisan commands
- Error logging and debugging
- Documentation searches

**Examples of Laravel Boost MCP usage:**
- Check routes: Use ListRoutes tool
- Database schema: Use DatabaseSchema tool
- Run Artisan commands: Use appropriate MCP tools
- Check logs: Use ReadLogEntries tool
- Application info: Use ApplicationInfo tool

## 6) TodoWrite Protocol

### Usage Guidelines
- Examples (pseudocode):
  - General: `TodoWrite({ todos: ["Migrate DB", "Update docs"], context: "why and where" })`
  - Vibe Kanban: `TodoWrite({ todos: ["VK create task: Diagnosis for blog-feature"], context: "project=<proj-id>; link to .claude/kanban/blog-feature.prompt.md" })`

### Error Recovery
- If `TodoWrite` fails with `The required parameter 'todos' is missing`, reissue it with a non-empty `todos` array.
- Record created Todo IDs and link them in artifacts (implementation/test notes) for traceability.

## 7) Kanban (Vibe) — Ringkas

### Guidelines
- **Trigger**: Only create Vibe Kanban tasks when the user includes "cTask".
- **Tooling**: Use the Vibe Kanban MCP (agent/tool API), not the CLI, unless the user explicitly requests the CLI.
- **Scope**: Do not implement code when asked to "create task(s)"—only create/update the task in Kanban.
- **Single PRD task**: For new features, create exactly one PRD‑style task with an elaborative title and numbered objective actions.
- **Traceability**: Run TodoWrite first (non-empty `todos`), then perform the MCP action; include `project_id` and `task_id` on all task‑scoped operations; link `.claude/kanban/<feature-slug>.*` artifacts.
- **Artifacts**: Save the PRD at `.claude/kanban/<feature-slug>.prd.md` and link it in the task body.

### PRD Contents (must include)
1) Background & rationale (why now; reference files/routes/models by path).
2) Objectives & non‑goals.
3) Architecture & data model (migrations, models, relationships, indexes, constraints).
4) API/Routes & controllers (methods, URIs, names; validation & auth rules).
5) Frontend (Inertia SPA — React default, Vue legacy) pages/components, state, autosave/infinite scroll, UX details.
6) Tooling usage (Laravel Boost MCP: routes, schema, tinker, docs search, logs) with example calls.
7) Commands (Artisan/bun) to scaffold/migrate/build/test.
8) Code examples (minimal PHP/Vue snippets for patterns—not full impls).
9) Validation, errors, permissions (rules, common failures, logging/retries).
10) Acceptance criteria (exhaustive, testable; include edge cases & permission matrix).
11) Test plan (Pest/PHPUnit + manual/inertia behaviors; data setup/teardown).
12) Rollout & risks (migration order, data issues, fallback/rollback).

### MCP Parameters & Recovery
- Always include `project_id` and `task_id` for task actions.
- On `InputValidationError` or missing IDs, reissue with required fields; log attempted action and IDs in `.claude/kanban/<feature-slug>.*` when applicable.
- Before any task‑scoped action, create/update a Todo via TodoWrite describing the operation and referencing the IDs.

## 8) Perintah Umum (jalankan dari webapp/)
- `bun run dev` — Vite dev (frontend)
- `composer run dev` — PHP server, queues, Reverb, Vite via bun
- `bun run build` — build produksi
- `bun run lint` / `bun run format`
- `composer install` / `bun install`
- `php artisan test`

## 9) Quality Checklist (sebelum submit)
- [ ] Pakai komponen/pola desain minimal (clean-card/clean-button, tipografi/spacing)
- [ ] Warna via CSS variables; theme-aware (`text-foreground`, `text-muted-foreground`)
- [ ] Interaksi via Inertia (no raw fetch, kecuali pengecualian JSON-only)
- [ ] Rute/Controller/Model sesuai konvensi; tes berjalan untuk logic baru
- [ ] Tidak ada gaya "cyber-*" atau efek visual berlebihan

## 10) Error Handling & Development Workflow

### Error Handling
- Use Browser Logs (Laravel Boost MCP Tools) to check latest errors
- Confirm specific error before fixing (check last 2 entries only)
- Don't assume - always verify the exact error first

### Development Workflow
- **Before implementing any solution:** Check if Inertia.js architecture can be used
- **For API operations:** Default to @inertiajs/react methods — use `router.post/put/patch/delete` or `useForm` (avoid fetch/axios)
- **Laravel Integration:** Leverage Laravel Boost MCP tools for project analysis and debugging
- **If unsure, research first:** If you are not sure what to do, use search or fetch the documentation before proceeding with an implementation.

### Single Page Application Guidelines
- **Minimize web reloading** - Always use Inertia.js SPA principles
- **Form Submissions:** Use `router.post()` with proper success/error handling
- **Navigation:** Use `router.visit()` or `Link` component instead of traditional links
- **Data Fetching:** Prefer server-side data loading through Inertia props over client-side API calls

## 11) AI Prompt Creation Guidelines

**When creating AI prompts for implementation tasks:**
- Always provide conversation context explaining WHY changes are needed
- Include relevant existing codebase functions/models/structure as context
- Reference specific files, methods, and database schemas that exist
- Keep code examples concise - show structure, not full implementations
- Explain the educational/technical problem being solved
- Ensure next AI understands the reasoning behind architectural decisions

## 12) Pemeliharaan Aturan
- Tambah/ubah aturan hanya jika pola baru muncul konsisten atau mencegah bug umum.
- Aturan harus singkat, spesifik, dan bisa dipraktikkan. Referensikan kode nyata bila perlu.
- Pindahkan detail panjang ke dokumen khusus (DESIGN_SYSTEM.md, TOOLING.md, KANBAN.md). AGENTS.md harus tetap ringkas.
