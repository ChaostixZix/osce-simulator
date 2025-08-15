<?php

use App\Http\Controllers\OSCEController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// OSCE Routes
Route::prefix('osce')->name('osce.')->group(function () {
    // Main OSCE interface
    Route::get('/', [OSCEController::class, 'index'])->name('index');
    
    // API endpoints for OSCE functionality
    Route::post('/start', [OSCEController::class, 'startOSCE'])->name('start');
    Route::post('/select-case', [OSCEController::class, 'selectCase'])->name('select-case');
    Route::post('/process-input', [OSCEController::class, 'processInput'])->name('process-input');
    Route::get('/state', [OSCEController::class, 'getState'])->name('state');
    Route::post('/end-case', [OSCEController::class, 'endCase'])->name('end-case');
    Route::post('/reset', [OSCEController::class, 'reset'])->name('reset');
    
    // Session management
    Route::get('/sessions', [OSCEController::class, 'listSessions'])->name('sessions');
    Route::get('/sessions/{sessionId}', [OSCEController::class, 'getSessionHistory'])->name('session-history');
});

// Chat Mode Routes
Route::get('/chat', function () {
    return Inertia::render('Chat/Dashboard');
})->name('chat');

require __DIR__.'/auth.php';
