<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SoapAttachmentController;
use App\Http\Controllers\SoapBoardController;
use App\Http\Controllers\SoapCommentController;
use App\Http\Controllers\SoapNoteController;
use App\Http\Controllers\SoapPageController;

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
	Route::get('api/osce/sessions/{session}/timer', [App\Http\Controllers\OsceController::class, 'getSessionTimer']);
	Route::post('api/osce/sessions/{session}/complete', [App\Http\Controllers\OsceController::class, 'completeSession']);
	Route::post('api/osce/sessions/{session}/extend', [App\Http\Controllers\OsceController::class, 'extendSession']);
	
	// OSCE Chat routes
	Route::post('api/osce/chat/start', [App\Http\Controllers\OsceChatController::class, 'startChat']);
	Route::post('api/osce/chat/message', [App\Http\Controllers\OsceChatController::class, 'sendMessage']);
	Route::get('api/osce/chat/history/{session}', [App\Http\Controllers\OsceChatController::class, 'getChatHistory']);
	
	// OSCE Examination routes (legacy ordering replaced by clinical reasoning system)
	Route::post('osce/order-procedure', [App\Http\Controllers\OsceController::class, 'orderProcedure'])->name('osce.order-procedure');
	Route::post('osce/perform-examination', [App\Http\Controllers\OsceController::class, 'performExamination'])->name('osce.perform-examination');

	// Clinical reasoning ordering API
	Route::post('api/osce/order-tests', [App\Http\Controllers\OsceController::class, 'orderTests']);
	Route::post('api/osce/refresh-results/{session}', [App\Http\Controllers\OsceController::class, 'refreshTestResults']);
	Route::get('api/medical-tests/search', [App\Http\Controllers\MedicalTestController::class, 'search']);
	Route::get('api/medical-tests/categories', [App\Http\Controllers\MedicalTestController::class, 'getCategories']);
	Route::post('api/osce/cases/{case}/duration', [App\Http\Controllers\OsceController::class, 'updateCaseDuration']);
	
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

	// SOAP routes
	// NEW PAGES
	Route::get('soap', [SoapBoardController::class, 'index'])->name('soap.board');
	Route::get('soap/patients/{patient}', [SoapPageController::class, 'show'])->name('soap.page');

	// NOTES
	Route::post('soap/patients/{patient}/notes', [SoapNoteController::class, 'store'])->name('soap.store');
	Route::put('soap/notes/{note}', [SoapNoteController::class, 'update'])->name('soap.update');
	Route::post('soap/notes/{note}/finalize', [SoapNoteController::class, 'finalize'])->name('soap.finalize');
	Route::delete('soap/notes/{note}', [SoapNoteController::class, 'destroy'])->name('soap.destroy');
	Route::post('soap/notes/{note}/restore', [SoapNoteController::class, 'restore'])
		 ->middleware('can:restore,note')->name('soap.restore'); // admin only

	// ATTACHMENTS
	Route::post('soap/notes/{note}/attachments', [SoapAttachmentController::class, 'store'])->name('soap.attach');

	// COMMENTS (lazy JSON)
	Route::get('soap/notes/{note}/comments', [SoapCommentController::class, 'index'])->name('soap.comments.index');
	Route::post('soap/notes/{note}/comments', [SoapCommentController::class, 'store'])->name('soap.comments.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';