<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts
     */
    public function index(Request $request)
    {
        $query = Post::with(['category', 'author', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc');

        // Apply filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('tag')) {
            $query->byTag($request->tag);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate(15);

        // Get categories and tags for filters
        $categories = Category::active()->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $request->only(['category', 'tag', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new blog post
     */
    public function create()
    {
        $this->authorize('create', Post::class);

        $categories = Category::active()->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Blog/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created blog post in storage
     */
    public function store(StoreBlogPostRequest $request)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();
        $validated['author_id'] = Auth::id();

        // Handle published_at based on status
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);

        // Attach tags if provided
        if (!empty($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }

        return redirect()->route('blog.index')
            ->with('success', 'Blog post created successfully!');
    }

    /**
     * Display the specified blog post
     */
    public function show(Post $post)
    {
        // Only show published posts to public
        if ($post->status !== 'published' || !$post->published_at) {
            abort(404);
        }

        // Increment view count
        $post->incrementViews();

        // Load relationships
        $post->load(['category', 'author', 'tags']);

        // Get related posts
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->limit(3)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    /**
     * Show the form for editing the specified blog post
     */
    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $post->load('tags');
        $categories = Category::active()->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Blog/Edit', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Update the specified blog post in storage
     */
    public function update(UpdateBlogPostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        // Handle published_at based on status
        if ($validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] !== 'published') {
            $validated['published_at'] = null;
        }

        $post->update($validated);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return redirect()->route('blog.show', $post)
            ->with('success', 'Blog post updated successfully!');
    }

    /**
     * Remove the specified blog post from storage
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('blog.index')
            ->with('success', 'Blog post deleted successfully!');
    }
}