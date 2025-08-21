# Blog Feature — Implementation Report

| File | Purpose | Key Notes |
| :--- | :--- | :--- |
| `database/migrations/*create_blog_categories_table.php` | Migration | Creates the `blog_categories` table. |
| `database/migrations/*create_blog_tags_table.php` | Migration | Creates the `blog_tags` table. |
| `database/migrations/*create_blog_posts_table.php` | Migration | Creates the `blog_posts` table with relationships. |
| `database/migrations/*create_blog_post_tag_table.php` | Migration | Creates the pivot table for posts and tags. |
| `app/Models/BlogPost.php` | Model | Eloquent model for blog posts, including relationships and scopes. |
| `app/Models/BlogCategory.php` | Model | Eloquent model for blog categories. |
| `app/Models/BlogTag.php` | Model | Eloquent model for blog tags. |
| `app/Http/Requests/BlogPostRequest.php` | Form Request | Validation rules for creating and updating blog posts. |
| `app/Http/Controllers/BlogController.php` | Controller | Handles public-facing blog routes (`index` and `show`). |
| `app/Http/Controllers/Admin/BlogPostController.php` | Controller | Handles admin CRUD operations for blog posts. |
| `routes/web.php` | Routes | Defines public and admin routes for the blog feature. |
| `resources/js/Pages/Blog/Index.vue` | Vue Page | Frontend page to display a list of blog posts. |
| `resources/js/Pages/Blog/Show.vue` | Vue Page | Frontend page to display a single blog post. |
| `resources/js/Pages/Admin/Blog/Index.vue` | Vue Page | Admin page to list and manage blog posts. |
| `resources/js/Pages/Admin/Blog/Create.vue` | Vue Page | Admin page for the create blog post form. |
| `resources/js/Pages/Admin/Blog/Edit.vue` | Vue Page | Admin page for the edit blog post form. |
| `database/factories/BlogCategoryFactory.php` | Factory | Factory for creating `BlogCategory` instances for testing/seeding. |
| `database/factories/BlogTagFactory.php` | Factory | Factory for creating `BlogTag` instances for testing/seeding. |
| `database/factories/BlogPostFactory.php` | Factory | Factory for creating `BlogPost` instances for testing/seeding. |
