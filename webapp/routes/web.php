<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', fn () => Inertia::render('Welcome'));

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('osce', [App\Http\Controllers\OsceController::class, 'index'])->name('osce');
    Route::get('api/osce/cases', [App\Http\Controllers\OsceController::class, 'getCases']);
    Route::get('api/osce/sessions', [App\Http\Controllers\OsceController::class, 'getUserSessions']);
    Route::post('api/osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSession']);
    Route::get('mcq-demo', [App\Http\Controllers\MCQController::class, 'index'])->name('mcq-demo');
    
    // Forum routes
    Route::get('forum', [App\Http\Controllers\ForumController::class, 'index'])->name('forum');
    
    // Forum API routes
    Route::prefix('api/forum')->group(function () {
        Route::post('posts', [App\Http\Controllers\ForumController::class, 'store'])->name('forum.posts.store');
        Route::get('feed', [App\Http\Controllers\ForumController::class, 'getFeed'])->name('forum.feed');
        Route::post('posts/{post}/like', [App\Http\Controllers\ForumController::class, 'toggleLike'])->name('forum.posts.like');
        Route::post('posts/{post}/retweet', [App\Http\Controllers\ForumController::class, 'toggleRetweet'])->name('forum.posts.retweet');
        Route::post('posts/{post}/bookmark', [App\Http\Controllers\ForumController::class, 'toggleBookmark'])->name('forum.posts.bookmark');
        Route::delete('posts/{post}', [App\Http\Controllers\ForumController::class, 'destroy'])->name('forum.posts.destroy');
        
        Route::post('users/{user}/follow', [App\Http\Controllers\ForumController::class, 'toggleFollow'])->name('forum.users.follow');
        Route::get('users/{user}/profile', [App\Http\Controllers\ForumController::class, 'getUserProfile'])->name('forum.users.profile');
        
        Route::get('notifications', [App\Http\Controllers\ForumController::class, 'getNotifications'])->name('forum.notifications');
        Route::patch('notifications/{notification}/read', [App\Http\Controllers\ForumController::class, 'markNotificationAsRead'])->name('forum.notifications.read');
        
        Route::get('search', [App\Http\Controllers\ForumController::class, 'search'])->name('forum.search');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
