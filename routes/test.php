<?php

use Illuminate\Support\Facades\Route;

// Test route to verify CSRF is disabled
Route::get('/test-csrf', function () {
    return 'CSRF test endpoint';
});

Route::post('/test-csrf', function () {
    return 'CSRF token validation passed!';
});