# AI Development Rules and Guidelines

This document consolidates the key rules and guidelines for AI-assisted development in this Laravel + Inertia.js + Vue.js project.

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

## Technology Stack & Versions
- **PHP** - 8.2.29
- **Laravel Framework** - v12
- **Inertia Laravel** - v2
- **Vue.js** - v3  
- **Tailwind CSS** - v4
- **Pest** - v3
- **Laravel Pint** - v1

## Development Workflow

### Component & Page Management
- **ShadCN Vue is already installed** - Don't reinstall it. Install specific components only when needed
- **New Page Creation Process:**
  1. Create Vue page in `Resources/js/Pages/`
  2. Add page to AppSidebar.vue navigation
  3. Create corresponding Laravel Controller
  4. Add routes in web.php with proper Inertia responses

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

### Vue + Inertia
- Vue components must have single root element
- Use `router.visit()` or `<Link>` for navigation
- Use `router.post()` for form handling, not regular forms
- Minimize web reloading - follow SPA principles

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
