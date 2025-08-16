<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\PostInteraction;
use App\Models\User;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    /**
     * Display the forum page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get posts for the current user (for you feed)
        $posts = Post::with(['user', 'interactions'])
            ->public()
            ->latest()
            ->paginate(20);

        // Get trending topics (simplified without hashtags)
        $trendingTopics = [
            ['id' => 1, 'topic' => 'VueJS', 'posts' => '12.5K posts', 'trending' => 'up'],
            ['id' => 2, 'topic' => 'Laravel', 'posts' => '8.2K posts', 'trending' => 'up'],
            ['id' => 3, 'topic' => 'WebDev', 'posts' => '15.7K posts', 'trending' => 'stable'],
            ['id' => 4, 'topic' => 'AI', 'posts' => '23.1K posts', 'trending' => 'up'],
            ['id' => 5, 'topic' => 'OpenSource', 'posts' => '6.8K posts', 'trending' => 'down'],
        ];

        // Get users to follow (excluding already followed users)
        $whoToFollow = User::where('id', '!=', $user->id)
            ->where('is_private', false)
            ->inRandomOrder()
            ->limit(3)
            ->get(['id', 'name', 'username', 'avatar', 'bio', 'is_verified']);

        // Get unread notifications count
        $unreadNotificationsCount = $user->notifications()->unread()->count();

        return Inertia::render('Forum', [
            'posts' => PostResource::collection($posts),
            'trendingTopics' => $trendingTopics,
            'whoToFollow' => UserResource::collection($whoToFollow),
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }

    /**
     * Store a new post.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:280',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'location' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $images = [];

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('forum/posts', 'public');
                $images[] = Storage::url($path);
            }
        }

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $request->content,
            'images' => $images,
            'location' => $request->location,
            'scheduled_at' => $request->scheduled_at,
            'is_public' => true,
        ]);

        // Return the created post with user data
        $post->load('user');

        return response()->json([
            'message' => 'Post created successfully',
            'post' => new PostResource($post),
        ], 201);
    }

    /**
     * Get posts for different feeds.
     */
    public function getFeed(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->get('type', 'for-you');
        $page = $request->get('page', 1);

        $query = Post::with(['user', 'interactions'])->public();

        switch ($type) {
            case 'following':
                $query = $query->fromFollowedUsers($user);
                break;
            case 'trending':
                $query = $query->trending();
                break;
            default: // for-you
                $query = $query->latest();
                break;
        }

        $posts = $query->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'posts' => PostResource::collection($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Like/unlike a post.
     */
    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $user = Auth::user();
        $interaction = PostInteraction::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('type', 'like')
            ->first();

        if ($interaction) {
            $interaction->delete();
            $isLiked = false;
            $message = 'Post unliked';
        } else {
            PostInteraction::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'type' => 'like',
            ]);
            $isLiked = true;
            $message = 'Post liked';

            // Create notification for post owner
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'from_user_id' => $user->id,
                    'type' => 'like',
                    'data' => ['post_id' => $post->id],
                ]);
            }
        }

        return response()->json([
            'message' => $message,
            'is_liked' => $isLiked,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    /**
     * Retweet/unretweet a post.
     */
    public function toggleRetweet(Request $request, Post $post): JsonResponse
    {
        $user = Auth::user();
        $interaction = PostInteraction::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('type', 'retweet')
            ->first();

        if ($interaction) {
            $interaction->delete();
            $isRetweeted = false;
            $message = 'Post unretweeted';
        } else {
            PostInteraction::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'type' => 'retweet',
            ]);
            $isRetweeted = true;
            $message = 'Post retweeted';

            // Create notification for post owner
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'from_user_id' => $user->id,
                    'type' => 'retweet',
                    'data' => ['post_id' => $post->id],
                ]);
            }
        }

        return response()->json([
            'message' => $message,
            'is_retweeted' => $isRetweeted,
            'retweets_count' => $post->fresh()->retweets_count,
        ]);
    }

    /**
     * Bookmark/unbookmark a post.
     */
    public function toggleBookmark(Request $request, Post $post): JsonResponse
    {
        $user = Auth::user();
        $interaction = PostInteraction::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('type', 'bookmark')
            ->first();

        if ($interaction) {
            $interaction->delete();
            $isBookmarked = false;
            $message = 'Post unbookmarked';
        } else {
            PostInteraction::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'type' => 'bookmark',
            ]);
            $isBookmarked = true;
            $message = 'Post bookmarked';
        }

        return response()->json([
            'message' => $message,
            'is_bookmarked' => $isBookmarked,
        ]);
    }

    /**
     * Follow/unfollow a user.
     */
    public function toggleFollow(Request $request, User $userToFollow): JsonResponse
    {
        $user = Auth::user();

        if ($user->id === $userToFollow->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        if ($user->isFollowing($userToFollow)) {
            $user->following()->detach($userToFollow->id);
            $isFollowing = false;
            $message = 'User unfollowed';
        } else {
            $user->following()->attach($userToFollow->id);
            $isFollowing = true;
            $message = 'User followed';

            // Create notification
            Notification::create([
                'user_id' => $userToFollow->id,
                'from_user_id' => $user->id,
                'type' => 'follow',
                'data' => [],
            ]);
        }

        return response()->json([
            'message' => $message,
            'is_following' => $isFollowing,
            'followers_count' => $userToFollow->fresh()->followers_count,
        ]);
    }

    /**
     * Get user's notifications.
     */
    public function getNotifications(): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->with('fromUser')
            ->latest()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationAsRead(Notification $notification): JsonResponse
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Search posts and users.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        // Search posts
        $posts = Post::with(['user'])
            ->where('content', 'like', "%{$query}%")
            ->public()
            ->latest()
            ->limit(10)
            ->get();

        // Search users
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->orWhere('bio', 'like', "%{$query}%")
            ->where('is_private', false)
            ->limit(10)
            ->get(['id', 'name', 'username', 'avatar', 'bio', 'is_verified']);

        return response()->json([
            'results' => [
                'posts' => PostResource::collection($posts),
                'users' => UserResource::collection($users),
            ],
        ]);
    }

    /**
     * Get user profile.
     */
    public function getUserProfile(User $user): JsonResponse
    {
        $currentUser = Auth::user();
        
        $user->load(['posts' => function ($query) {
            $query->public()->latest()->limit(10);
        }]);

        $isFollowing = $currentUser ? $currentUser->isFollowing($user) : false;
        $isFollowedBy = $currentUser ? $currentUser->isFollowedBy($user) : false;

        return response()->json([
            'user' => new UserResource($user),
            'is_following' => $isFollowing,
            'is_followed_by' => $isFollowedBy,
        ]);
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post): JsonResponse
    {
        $user = Auth::user();

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated images
        if (!empty($post->images)) {
            foreach ($post->images as $image) {
                $path = str_replace('/storage/', '', $image);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}