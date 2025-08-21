<?php

namespace App\Http\Controllers;

use App\Http\Resources\ForumPostResource;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'comments'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return Inertia::render('Forum/Index', [
            'posts' => ForumPostResource::collection($posts),
        ]);
    }

    public function show(Post $post)
    {
        $post->load(['user', 'comments.user']);

        return Inertia::render('Forum/Show', [
            'post' => new ForumPostResource($post),
            'comments' => CommentResource::collection($post->comments),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create([
            'title' => $request->title ?: '',
            'content' => $request->content,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('forum.index');
    }

    public function indexApi(Request $request)
    {
        $posts = Post::with(['user'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return ForumPostResource::collection($posts);
    }

    public function storeApi(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $post = Post::create([
            'title' => '', // Empty title for Twitter-like posts
            'content' => $request->content,
            'user_id' => auth()->id(),
        ]);

        $post->load(['user']);

        return response()->json(new ForumPostResource($post), 201);
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $post->update($request->only(['title', 'content']));

        return redirect()->route('forum.index');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('forum.index');
    }
}

