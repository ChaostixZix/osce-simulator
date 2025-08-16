<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use function Pest\Laravel\{get, post, put, delete, assertDatabaseHas, assertDatabaseMissing};

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    $this->tag = Tag::factory()->create();
});

describe('Blog Index Page', function () {
    it('can display the blog index page', function () {
        get('/blog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/Index'));
    });

    it('displays published posts on the index page', function () {
        $publishedPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        
        $draftPost = Post::factory()->draft()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $publishedPost->id)
            );
    });

    it('paginates blog posts', function () {
        Post::factory(25)->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 15) // Default pagination
                ->has('posts.links')
            );
    });

    it('can filter posts by category', function () {
        $category2 = Category::factory()->create();
        
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        
        $post2 = Post::factory()->published()->create([
            'category_id' => $category2->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog?category=' . $this->category->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $post1->id)
            );
    });

    it('can filter posts by tag', function () {
        $tag2 = Tag::factory()->create();
        
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        $post1->tags()->attach($this->tag);
        
        $post2 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        $post2->tags()->attach($tag2);

        get('/blog?tag=' . $this->tag->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $post1->id)
            );
    });

    it('can search posts', function () {
        $post1 = Post::factory()->published()->create([
            'title' => 'Laravel Tips and Tricks',
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        
        $post2 = Post::factory()->published()->create([
            'title' => 'Vue.js Components Guide',
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog?search=Laravel')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $post1->id)
            );
    });
});

describe('Blog Show Page', function () {
    it('can display a published post', function () {
        $post = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog/' . $post->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Show')
                ->where('post.id', $post->id)
                ->where('post.title', $post->title)
            );
    });

    it('increments view count when viewing a post', function () {
        $post = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
            'views_count' => 0,
        ]);

        get('/blog/' . $post->slug);

        expect($post->fresh()->views_count)->toBe(1);
    });

    it('cannot display draft posts', function () {
        $post = Post::factory()->draft()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog/' . $post->slug)
            ->assertNotFound();
    });

    it('cannot display archived posts', function () {
        $post = Post::factory()->archived()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog/' . $post->slug)
            ->assertNotFound();
    });
});

describe('Blog Management - Create', function () {
    it('can display the create post form', function () {
        $this->actingAs($this->user);

        get('/blog/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Create')
                ->has('categories')
                ->has('tags')
            );
    });

    it('can create a new post', function () {
        $this->actingAs($this->user);

        $postData = [
            'title' => 'New Blog Post',
            'content' => 'This is the content of the blog post.',
            'excerpt' => 'This is the excerpt.',
            'category_id' => $this->category->id,
            'tags' => [$this->tag->id],
            'status' => 'published',
        ];

        post('/blog', $postData)
            ->assertRedirect('/blog');

        assertDatabaseHas('posts', [
            'title' => 'New Blog Post',
            'author_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);
    });

    it('validates required fields when creating a post', function () {
        $this->actingAs($this->user);

        post('/blog', [])
            ->assertSessionHasErrors(['title', 'content', 'category_id']);
    });

    it('validates title uniqueness when creating a post', function () {
        $this->actingAs($this->user);
        
        $existingPost = Post::factory()->create([
            'title' => 'Existing Title',
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        post('/blog', [
            'title' => 'Existing Title',
            'content' => 'Some content',
            'category_id' => $this->category->id,
        ])
            ->assertSessionHasErrors(['title']);
    });

    it('auto-generates slug from title when creating a post', function () {
        $this->actingAs($this->user);

        post('/blog', [
            'title' => 'This is a Test Post',
            'content' => 'Content here',
            'category_id' => $this->category->id,
        ]);

        assertDatabaseHas('posts', [
            'slug' => 'this-is-a-test-post',
        ]);
    });
});

describe('Blog Management - Edit', function () {
    it('can display the edit post form', function () {
        $this->actingAs($this->user);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog/' . $post->slug . '/edit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Edit')
                ->where('post.id', $post->id)
                ->has('categories')
                ->has('tags')
            );
    });

    it('can update a post', function () {
        $this->actingAs($this->user);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        put('/blog/' . $post->slug, [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'category_id' => $this->category->id,
        ])
            ->assertRedirect('/blog/' . $post->slug);

        assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    });

    it('validates required fields when updating a post', function () {
        $this->actingAs($this->user);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        put('/blog/' . $post->slug, [
            'title' => '',
            'content' => '',
        ])
            ->assertSessionHasErrors(['title', 'content']);
    });

    it('cannot edit posts by other authors', function () {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        get('/blog/' . $post->slug . '/edit')
            ->assertForbidden();
    });
});

describe('Blog Management - Delete', function () {
    it('can delete a post', function () {
        $this->actingAs($this->user);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        delete('/blog/' . $post->slug)
            ->assertRedirect('/blog');

        assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    });

    it('cannot delete posts by other authors', function () {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);
        
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        delete('/blog/' . $post->slug)
            ->assertForbidden();
    });
});

describe('Blog Models', function () {
    it('automatically generates slug when creating a post', function () {
        $post = Post::factory()->create([
            'title' => 'Test Post Title',
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        expect($post->slug)->toBe('test-post-title');
    });

    it('automatically generates excerpt from content when empty', function () {
        $content = str_repeat('This is a long content. ', 50);
        
        $post = Post::factory()->create([
            'content' => $content,
            'excerpt' => null,
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        expect($post->excerpt)->not->toBeNull();
        expect(strlen($post->excerpt))->toBeLessThanOrEqual(160);
    });

    it('calculates reading time correctly', function () {
        $content = str_repeat('word ', 200); // Approximately 200 words
        
        $post = Post::factory()->create([
            'content' => $content,
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        expect($post->reading_time)->toBe(1); // Should be 1 minute for 200 words
    });

    it('scopes published posts correctly', function () {
        $publishedPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        
        $draftPost = Post::factory()->draft()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);

        $publishedPosts = Post::published()->get();

        expect($publishedPosts)->toHaveCount(1);
        expect($publishedPosts->first()->id)->toBe($publishedPost->id);
    });

    it('establishes correct relationships', function () {
        $post = Post::factory()->create([
            'category_id' => $this->category->id,
            'author_id' => $this->user->id,
        ]);
        
        $post->tags()->attach($this->tag);

        expect($post->category)->toBeInstanceOf(Category::class);
        expect($post->author)->toBeInstanceOf(User::class);
        expect($post->tags)->toHaveCount(1);
        expect($post->tags->first())->toBeInstanceOf(Tag::class);
    });
});