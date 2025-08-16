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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
