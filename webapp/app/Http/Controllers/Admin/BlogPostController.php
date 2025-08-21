<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::query()
            ->with('author', 'category', 'tags')
            ->withFilters($request->only('q', 'category', 'tag', 'status'))
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Blog/Create', [
            'categories' => BlogCategory::all(),
            'tags' => BlogTag::all(),
        ]);
    }

    public function store(BlogPostRequest $request)
    {
        $post = BlogPost::create($request->validated() + [
            'author_id' => auth()->id(),
            'slug' => Str::slug($request->title),
        ]);

        if ($request->hasFile('cover_image')) {
            $post->update(['cover_image_path' => $request->file('cover_image')->store('blog', 'public')]);
        }

        $post->tags()->sync($request->tags);

        return redirect()->route('admin.blog.index');
    }

    public function edit(BlogPost $post)
    {
        $post->load('tags');
        return Inertia::render('Admin/Blog/Edit', [
            'post' => $post,
            'categories' => BlogCategory::all(),
            'tags' => BlogTag::all(),
        ]);
    }

    public function update(BlogPostRequest $request, BlogPost $post)
    {
        $post->update($request->validated());

        if ($request->hasFile('cover_image')) {
            $post->update(['cover_image_path' => $request->file('cover_image')->store('blog', 'public')]);
        }

        $post->tags()->sync($request->tags);

        return redirect()->route('admin.blog.index');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();

        return redirect()->route('admin.blog.index');
    }
}
