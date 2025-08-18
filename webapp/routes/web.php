<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LandingController;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::middleware([
	'auth',
	ValidateSessionWithWorkOS::class,
])->group(function () {
	Route::get('dashboard', function () {
		return Inertia::render('Dashboard');
	})->name('dashboard');
	
	Route::get('osce', [App\Http\Controllers\OsceController::class, 'index'])->name('osce');
	Route::get('osce/chat/{session}', [App\Http\Controllers\OsceController::class, 'showChat'])->name('osce.chat');
	Route::get('api/osce/cases', [App\Http\Controllers\OsceController::class, 'getCases']);
	Route::get('api/osce/sessions', [App\Http\Controllers\OsceController::class, 'getUserSessions']);
	Route::post('api/osce/sessions/start', [App\Http\Controllers\OsceController::class, 'startSession']);
	
	// OSCE Chat routes
	Route::post('api/osce/chat/start', [App\Http\Controllers\OsceChatController::class, 'startChat']);
	Route::post('api/osce/chat/message', [App\Http\Controllers\OsceChatController::class, 'sendMessage']);
	Route::get('api/osce/chat/history/{session}', [App\Http\Controllers\OsceChatController::class, 'getChatHistory']);
	
	// OSCE Examination routes (legacy ordering replaced by clinical reasoning system)
	Route::post('osce/order-procedure', [App\Http\Controllers\OsceController::class, 'orderProcedure'])->name('osce.order-procedure');
	Route::post('osce/perform-examination', [App\Http\Controllers\OsceController::class, 'performExamination'])->name('osce.perform-examination');

	// Clinical reasoning ordering API
	Route::post('api/osce/order-tests', [App\Http\Controllers\OsceController::class, 'orderTests']);
	Route::get('api/medical-tests/search', [App\Http\Controllers\MedicalTestController::class, 'search']);
	Route::get('api/medical-tests/categories', [App\Http\Controllers\MedicalTestController::class, 'getCategories']);
	
	Route::get('mcq-demo', [App\Http\Controllers\MCQController::class, 'index'])->name('mcq-demo');

	// Forum routes
	Route::get('forum', [PostController::class, 'index'])->name('forum.index');
	Route::get('forum/{post}', [PostController::class, 'show'])->name('forum.show');
	Route::post('forum', [PostController::class, 'store'])->name('forum.store');
	Route::put('forum/{post}', [PostController::class, 'update'])->name('forum.update');
	Route::delete('forum/{post}', [PostController::class, 'destroy'])->name('forum.destroy');

	// Comment routes
	Route::post('forum/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
	Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
	Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
