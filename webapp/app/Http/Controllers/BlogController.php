<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::query()
            ->published()
            ->with('author', 'category', 'tags')
            ->withFilters($request->only('q', 'category', 'tag'))
            ->latest('published_at')
            ->paginate(10);

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->with('author', 'category', 'tags')
            ->firstOrFail();

        return Inertia::render('Blog/Show', [
            'post' => $post,
        ]);
    }
}
