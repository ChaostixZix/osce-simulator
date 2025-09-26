<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'auth/supabase/login',
        'auth/supabase/register',
        'auth/supabase/logout',
        'auth/supabase/forgot-password',
        'auth/supabase/reset-password',
        'auth/supabase/magic-link',
    ];
}