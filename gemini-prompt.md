# Gemini CLI Rules & Guidelines

Extracted from AGENTS.md - Complete rules for using Gemini CLI effectively.

## Purpose & Decision Matrix

**Gemini CLI Purpose**: Understand the codebase and features (functions/components), trace data flow, and update documentation.md with context.

**Order of Operations:**
1. Check @documentation.md (use if up to date)
2. Use Gemini CLI (targeted) to understand code paths; update documentation.md with context (avoid full reindex)
3. Use Laravel Boost MCP to run/inspect (artisan, routes, schema, logs) as needed

## Core Principles

### **PRIORITIZE SPECIFIC FEATURE ANALYSIS over full codebase analysis**

- Please use **targeted analysis** before implementing task - focus on **SPECIFIC functions/features**, NOT entire codebase
- **ALWAYS check if @documentation.md existed and is uptodate**, if it exists, please refer using that and don't reindex the codebase, but if its not uptodate then use Gemini CLI first when you try to understand this codebase and then update the documentation.md
- **Everytime you add a new knowledge**, please tell GEMINI.CLI to update the documentation.md, but give context, so gemini_cli doesnt reindex all the codebase again (this will cause to eat time)
- If Gemini CLI fails, **retry up to 3 times** before falling back to other methods

## Targeted Analysis Strategy

**ALWAYS prioritize specific feature analysis:**
1. **Identify specific function/feature** you need to understand or implement
2. **Target relevant files/directories** only for that feature
3. **Use detailed, specific prompts** with clear context and requirements
4. **Avoid full codebase analysis** unless absolutely necessary

## Retry Strategy

If a Gemini CLI command fails:
1. **First attempt**: Try the original command
2. **Second attempt**: Retry the same command (network/API issues)
3. **Third attempt**: Retry with simplified prompt or smaller scope
4. **Fourth attempt**: Fall back to manual file reading only if all Gemini attempts fail

## File and Directory Inclusion Syntax

Use the `@` syntax to include **SPECIFIC files and directories** relevant to your target feature. The paths should be relative to WHERE you run the gemini command.

**FOCUS ON RELEVANT FILES ONLY - NOT ENTIRE CODEBASE**

### Examples:

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

## Detailed Prompt Guidelines

**Always provide specific context and requirements:**

### ✅ GOOD - Detailed and Specific:
```bash
gemini -p "@src/chat/ @components/ChatRoom.jsx I'm implementing real-time chat functionality. Please analyze and explain: 1) How WebSocket connections are established and maintained, 2) Message format and data structure, 3) How typing indicators work, 4) Room joining/leaving logic, 5) Any authentication for chat access"
```

### ❌ BAD - Vague and General:
```bash
gemini -p "@src/ Analyze the codebase"
```

### ✅ GOOD - Focused Feature Analysis:
```bash
gemini -p "@components/DataTable.jsx @hooks/useTableData.js I need to add sorting and filtering to the data table. Please explain: 1) Current data fetching logic, 2) How table state is managed, 3) Existing sorting/filtering if any, 4) Prop structure and data flow, 5) Best approach to add new sorting columns"
```

### ✅ GOOD - Implementation Verification:
```bash
gemini -p "@src/auth/ @middleware/ I need to verify authentication security. Please check: 1) Are passwords properly hashed with salt?, 2) Is rate limiting implemented for login attempts?, 3) Are JWTs properly validated on protected routes?, 4) Any CSRF protection?, 5) Session management approach"
```

## When to Use Gemini CLI

### **PRIORITIZE for specific feature analysis:**
- Understanding SPECIFIC functions, components, or features
- Analyzing targeted file groups related to one functionality
- Verifying specific implementation patterns
- Getting context for implementing similar features
- Understanding data flow for particular features

### **Use ONLY when necessary for broader analysis:**
- Creating comprehensive documentation (after specific analysis)
- Understanding overall architecture (rare cases)
- Project structure overview (initial setup only)

## Full Codebase Analysis (When Necessary)

**Use full codebase analysis ONLY when:**
- You don't know where specific features are located
- You need to discover existing implementations
- Initial project exploration
- Finding related files for unknown features

### Discovery and Exploration Examples:

**Discover authentication system:**
```bash
gemini -a -p "Please explain how authentication works in this app. I need to understand: 1) What authentication method is used (JWT, sessions, etc), 2) Where are auth-related files located, 3) How login/logout works, 4) How protected routes are handled, 5) Any middleware or guards used"
```

### Two-Phase Approach (Recommended):

**Phase 1 - Discovery:**
```bash
gemini -a -p "I need to implement real-time notifications. Please help me discover: 1) Are there existing notification systems?, 2) What WebSocket or SSE implementations exist?, 3) Where are notification-related files located?, 4) How are notifications stored/managed?, 5) Any existing UI components for notifications"
```

**Phase 2 - Targeted Analysis:**
```bash
gemini -p "@src/notifications/ @components/NotificationCenter.jsx @api/notifications.js Now I found the notification files. Please analyze in detail: 1) How notifications are created and sent, 2) WebSocket connection handling, 3) Notification persistence and retrieval, 4) UI components and their props, 5) Any real-time update mechanisms"
```

## Integration with Laravel Boost MCP

### Overlap Policy:
- **If the goal is to understand the codebase/feature**: use Gemini CLI (targeted analysis) first
- **If the goal is to execute or inspect Laravel runtime** (artisan/routes/db/logs): use Laravel Boost MCP
- **When both could apply**: start with Gemini to map files/flow, then validate or execute with Laravel Boost MCP if runtime confirmation or Artisan actions are needed

### Retry & Fallback:
- **Gemini CLI**: retry up to 3x (then simplify scope) before manual file reading
- **Laravel Boost MCP**: on failure, inspect error/logs and correct parameters; avoid repeating the same failing call

## Laravel Boost MCP Integration

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

## Best Practices Summary

1. **Always check documentation.md first** before running Gemini CLI
2. **Use targeted analysis** with `@` syntax for specific files/directories
3. **Write detailed, specific prompts** with numbered requirements
4. **Retry up to 3 times** before fallback to manual methods
5. **Update documentation.md** after gaining new knowledge, with context to avoid full reindexing
6. **Prefer specific feature analysis** over full codebase analysis
7. **Use two-phase approach** for unknown features (discovery → targeted analysis)
8. **Integrate with Laravel Boost MCP** for Laravel runtime operations

## Quick Reference Commands

**Targeted Feature Analysis:**
```bash
gemini -p "@specific/files/ @relevant/components/ Detailed prompt with: 1) specific questions, 2) clear context, 3) numbered requirements"
```

**Full Codebase Discovery (only when necessary):**
```bash
gemini -a -p "Discovery prompt with: 1) what you're looking for, 2) specific questions, 3) clear objectives"
```

**Documentation Update Request:**
```bash
"Please update documentation.md with [specific context] about [feature/knowledge gained]"
```
