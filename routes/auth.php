<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SupabaseAuthController;
use Inertia\Inertia;

// Main authentication routes
Route::get('login', function () {
    $providers = config('supabase.providers', []);
    return Inertia::render('auth/Login', [
        'providers' => $providers
    ]);
})->name('login');

Route::get('register', function () {
    return Inertia::render('auth/Register');
})->name('register');

Route::get('forgot-password', function () {
    return Inertia::render('auth/ForgotPassword');
})->name('forgot-password');

// Supabase Authentication Routes
Route::prefix('auth/supabase')->group(function () {
    Route::get('login', [SupabaseAuthController::class, 'showLoginForm'])->name('supabase.login');
    Route::post('login', [SupabaseAuthController::class, 'login'])->name('supabase.login.submit');
    Route::get('register', [SupabaseAuthController::class, 'showRegistrationForm'])->name('supabase.register');
    Route::post('register', [SupabaseAuthController::class, 'register'])->name('supabase.register.submit');
    Route::get('oauth/{provider}', [SupabaseAuthController::class, 'redirectToProvider'])->name('supabase.oauth');
    Route::get('callback', [SupabaseAuthController::class, 'handleCallback'])->name('supabase.callback');
    Route::post('logout', [SupabaseAuthController::class, 'logout'])->name('supabase.logout');
    Route::get('forgot-password', [SupabaseAuthController::class, 'showForgotPasswordForm'])->name('supabase.forgot-password');
    Route::post('forgot-password', [SupabaseAuthController::class, 'forgotPassword'])->name('supabase.forgot-password.submit');
    Route::get('reset-password', [SupabaseAuthController::class, 'showResetPasswordForm'])->name('supabase.reset-password');
    Route::post('reset-password', [SupabaseAuthController::class, 'resetPassword'])->name('supabase.reset-password.submit');
    Route::post('magic-link', [SupabaseAuthController::class, 'magicLinkLogin'])->name('supabase.magic-link');
    
    // API routes for migration
    Route::middleware(['auth'])->group(function () {
        Route::get('migration-status', [SupabaseAuthController::class, 'checkMigrationStatus'])->name('supabase.migration-status');
        Route::post('migrate', [SupabaseAuthController::class, 'migrateToSupabase'])->name('supabase.migrate');
    });
})->middleware(['web']);
