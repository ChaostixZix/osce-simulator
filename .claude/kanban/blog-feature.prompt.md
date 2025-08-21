# Blog Feature — Diagnosis & Context (Prompt)

- Feature slug: blog-feature
- Project folder: /home/bintangputra/osc
- Architecture: Laravel backend + Vue via Inertia frontend (webapp/). Use SPA principles with Inertia routing and forms.
- Existing related code: Forum-like `Post` and `Comment` models plus `PostController` and `CommentController` already exist for community/forum use. We will add a separate, curated Blog feature to avoid coupling with forum entities.

## Why This Is Needed

The application currently offers forum-style posts and comments but lacks a curated, publisher-controlled Blog section for long-form content (announcements, guides, release notes). A dedicated Blog feature provides:
- Editorial control: Drafts, publishing workflow, scheduled posts, and featured/pinned content.
- Discoverability: Public index, SEO slugs/metadata, category/tag filters, search, and pagination.
- Presentation: Cover images, excerpts, and consistent layout distinct from community forum posts.

## Objectives (High Level)

User-facing:
- Browse a public blog index at `/blog` with pagination, search, and optional tag/category filtering.
- Read individual blog articles at `/blog/{slug}` with SEO-friendly URLs and meta tags.

Admin/Authoring:
- CRUD for blog posts (draft/publish, schedule publication, upload cover image, set tags/categories, SEO meta, featured flag).
- Simple authoring form using existing UI stack (no new editor dependency—use textarea + preview for now).
- Role/permission gate so only authorized users manage blog content.

## Scope

In scope:
- New Blog domain (separate from forum): models, migrations, controllers, routes, Vue pages/components, policies, and tests.
- Basic taxonomy: categories (one per post) and tags (many per post), with filters via query params.
- SEO fields: `meta_title`, `meta_description`; unique, stable slug; `published_at` timestamp.
- Image upload for cover image to `public` disk; display on index and show pages.
- Pagination, search (title/excerpt/body), sorting by publish date, featured support.
- Factories/seeders for local testing.

Out of scope (for this first iteration):
- WYSIWYG editor integration (tiptap/quill) — use textarea + markdown-like preview only.
- RSS feed, sitemap integration.
- Commenting on blog posts; reuse of forum comments.
- Full role system buildout; use a simple `can:admin`/policy gate placeholder for now.

## Data Model (Migrations + Eloquent)

Add new tables and models to keep the blog domain independent from forum posts:

1) `blog_categories`
- id (bigint, pk)
- name (string, unique)
- slug (string, unique, index)
- created_at, updated_at

2) `blog_tags`
- id (bigint, pk)
- name (string, unique)
- slug (string, unique, index)
- created_at, updated_at

3) `blog_posts`
- id (bigint, pk)
- author_id (foreignId -> users.id, index, cascade on delete)
- category_id (foreignId -> blog_categories.id, nullable, set null on delete)
- title (string)
- slug (string, unique, index)
- excerpt (text, nullable)
- content (longText)
- cover_image_path (string, nullable)
- featured (boolean, default false)
- status (enum/string: draft|published|scheduled, default draft)
- published_at (timestamp, nullable, index)
- meta_title (string, nullable)
- meta_description (string, nullable)
- created_at, updated_at

4) `blog_post_tag` (pivot)
- blog_post_id (foreignId -> blog_posts.id, cascade on delete)
- blog_tag_id (foreignId -> blog_tags.id, cascade on delete)
- primary key: [blog_post_id, blog_tag_id]

Eloquent models (under `webapp/app/Models`):
- `BlogPost` with relations: `author()` (belongsTo User), `category()` (belongsTo BlogCategory), `tags()` (belongsToMany BlogTag), scopes: `published()`, `featured()`, `search($q)`, `withFilters(params)`.
- `BlogCategory` with relations: `posts()` (hasMany BlogPost).
- `BlogTag` with relations: `posts()` (belongsToMany BlogPost).

Slug rules:
- Generate unique slug from title at create; enforce DB unique index.
- Keep slug stable after status transitions; allow manual override pre-publish; disallow breaking existing published slugs.

Status rules:
- `draft`: not publicly visible; `published`: public and visible; `scheduled`: not visible until `published_at <= now()`.

Image storage:
- Use Laravel `public` disk; store under `blog/` prefix; display via `Storage::url()`; require `php artisan storage:link` in setup docs.

## Routing (webapp/routes/web.php)

Public routes (no auth):
- GET `/blog` → `BlogController@index` (list, paginate, filters: `?q=`, `?tag=`, `?category=`)
- GET `/blog/{slug}` → `BlogController@show`

Admin routes (auth + can:admin or policy):
- Prefix: `/admin/blog`
- GET `/` → `Admin/BlogPostController@index` (list + filters)
- GET `/create` → `Admin/BlogPostController@create`
- POST `/` → `Admin/BlogPostController@store`
- GET `/{id}/edit` → `Admin/BlogPostController@edit`
- PUT/PATCH `/{id}` → `Admin/BlogPostController@update`
- DELETE `/{id}` → `Admin/BlogPostController@destroy`

Policy/Middleware:
- Protect admin routes with `auth` and a simple gate/policy, e.g., `can:manage-blog` or `can:admin`.

## Controllers (webapp/app/Http/Controllers)

- `BlogController` (public)
  - `index(Request $request)`: Load published posts with filters, eager-load category/tags/author, return Inertia `Blog/Index`.
  - `show(string $slug)`: Load published post by slug, 404 if not found/not published; return Inertia `Blog/Show`.

- `Admin/BlogPostController` (authz)
  - `index(Request $request)`: List posts any status; filters by status, q, tag, category; pagination.
  - `create()`: Load categories/tags for select options; return form page.
  - `store(BlogPostRequest $request)`: Validate and create post; handle image upload; set slug; if scheduled, set `published_at`.
  - `edit(BlogPost $post)`: Load post with relations plus taxonomy lists.
  - `update(BlogPostRequest $request, BlogPost $post)`: Validate; update; handle slug rules and image replacement; maintain published slug stability.
  - `destroy(BlogPost $post)`: Soft-delete optional; otherwise hard delete. For v1, hard delete is acceptable.

Requests (Form Requests under `webapp/app/Http/Requests`):
- `BlogPostRequest`: validation rules for create/update.

## Vue Pages (Inertia) (webapp/resources/js/Pages)

Public (Blog):
- `Blog/Index.vue`: grid/list layout, pagination, search input, tag/category filters, featured badge; cards show title, cover, excerpt, author, date, tags.
- `Blog/Show.vue`: full article view, title, cover, author, published date, category/tags, content, optional simple markdown render/preview.

Admin:
- `Admin/Blog/Index.vue`: table of posts with filters (status/q/tag/category), actions (edit/delete), publish state toggle, featured toggle.
- `Admin/Blog/Create.vue`: form fields (title, excerpt, content textarea, category select, tags multiselect, status, publish date, featured, cover file upload, meta fields); uses Inertia form helpers and shadcn-vue components if available.
- `Admin/Blog/Edit.vue`: same as create, prefilled; handles image preview/replacement, slug read-only if published.

Navigation:
- Add `/blog` to public navigation (if present). Do not disturb existing forum routes.

## Validation

`BlogPostRequest` rules:
- title: required, string, max:200
- excerpt: nullable, string, max:1000
- content: required, string
- category_id: nullable, exists:blog_categories,id
- tags: array of ids; each exists:blog_tags,id
- status: in:draft,published,scheduled
- published_at: required_if:status,scheduled; date
- cover_image: nullable, image, max:5120 (5MB)
- meta_title: nullable, string, max:70
- meta_description: nullable, string, max:160

## Filtering & Scopes

BlogPost scopes/utilities:
- `published()`: status=published and (published_at is null or <= now)
- `scheduledForPublic()`: status=scheduled and published_at <= now
- `featured()`: featured=true
- `search($q)`: search title/excerpt/content
- `withFilters($params)`: apply `q`, `tag`, `category`, `status`

Index behavior:
- Public index shows `published()` or scheduled posts whose `published_at <= now()`.
- Admin index shows all statuses with filters.

## Authorization

- Add a simple `BlogPostPolicy` or Gate `manage-blog` restricting admin CRUD to privileged users. For v1, assume a boolean `is_admin` column or an existing role mechanism; use `can:admin` middleware as placeholder.

## Tests (Pest/PHPUnit)

Feature tests (`webapp/tests/Feature/Blog`):
- Public index lists only published/visible posts and supports search/filter/pagination.
- Show route returns 404 for draft/unpublished slugs.
- Admin CRUD requires auth and permission; unauthorized users get 403.
- Creating/updating posts respects validation; image uploads saved; slug unique; slug stability after publish.

Unit tests (`webapp/tests/Unit/Blog`):
- Model relationships (author/category/tags), scopes (`published`, `featured`, `search`, `withFilters`).

Factories/Seeders:
- Factories for BlogPost, BlogCategory, BlogTag; optional seeder to demo sample content.

## File/Path Plan (Add/Modify)

Migrations (`webapp/database/migrations`):
- `create_blog_categories_table.php`
- `create_blog_tags_table.php`
- `create_blog_posts_table.php`
- `create_blog_post_tag_table.php`

Models (`webapp/app/Models`):
- `BlogPost.php`
- `BlogCategory.php`
- `BlogTag.php`

Requests (`webapp/app/Http/Requests`):
- `BlogPostRequest.php`

Controllers (`webapp/app/Http/Controllers`):
- `BlogController.php`
- `Admin/BlogPostController.php`

Policy (`webapp/app/Policies`):
- `BlogPostPolicy.php` (optional v1) and auth wiring in `AuthServiceProvider` or use `Gate::define('manage-blog')`.

Routes (`webapp/routes/web.php`):
- Public `/blog` and `/blog/{slug}`
- Admin `/admin/blog[...]` RESTful group

Pages (`webapp/resources/js/Pages`):
- `Blog/Index.vue`
- `Blog/Show.vue`
- `Admin/Blog/Index.vue`
- `Admin/Blog/Create.vue`
- `Admin/Blog/Edit.vue`

Factories/Seeders (`webapp/database/factories`, `webapp/database/seeders`):
- `BlogPostFactory.php`, `BlogCategoryFactory.php`, `BlogTagFactory.php`
- Optional `BlogSeeder.php`

## Implementation Constraints & Conventions

- Use Inertia.js SPA architecture for navigation and forms; prefer server-provided props to client-side fetching.
- Follow Laravel conventions (controllers, requests, policies, Eloquent relationships) and PSR-12.
- UI: Use existing shadcn-vue components where helpful; do not install new libraries.
- Keep imports ordered and Tailwind classes tidy; use `npm run lint` and `npm run format` in `webapp/` when applicable.
- Do not refactor or rename unrelated code; keep changes scoped to Blog feature.

## Acceptance Criteria (Must Pass)

- Public can navigate to `/blog`, see a paginated list of published posts, filter by tag/category (via query params), and search by title/excerpt/content.
- Public can read `/blog/{slug}`; draft/unpublished slugs return 404.
- Admin with permission can create, edit, delete posts; upload/replace cover image; set category/tags; set meta fields; schedule publish.
- Slug is unique and stable after publish; attempting to set a duplicate slug fails validation.
- Tests implemented and pass locally (`composer test`) for above scenarios.

## Rollout Notes

Setup:
- `cd webapp && composer install && npm install`
- Run migrations: `php artisan migrate`
- Ensure storage symlink: `php artisan storage:link`
- Seed optional data: `php artisan db:seed --class=BlogSeeder` (if added)

## Handoffs to Implementation & Testing Agents

- Implementation agent must implement only what is specified above and produce `.claude/kanban/blog-feature.implementation.md` with a table of changes (files, purpose, key notes).
- Testing agent must create meaningful Feature + Unit tests and produce `.claude/kanban/blog-feature.tests.md` summarizing coverage, scenarios, failures fixed, and final results.

## References

- Codebase structure: see AGENTS.md and CLAUDE.md for project layout and conventions.
- Inertia: Use `Inertia.get/post/put/delete` and `router.visit()` patterns; prefer server-side props.

