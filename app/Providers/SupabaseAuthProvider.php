<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class SupabaseAuthProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set auth configuration based on environment
        $useSupabase = env('USE_SUPABASE_AUTH', false);
        $dualMode = env('SUPABASE_DUAL_MODE', true);
        
        // Update config at runtime
        Config::set('auth.use_supabase', $useSupabase);
        Config::set('auth.supabase_dual_mode', $dualMode);
        
        // Share authentication mode with all views
        view()->share('authMode', [
            'use_supabase' => $useSupabase,
            'dual_mode' => $dualMode,
        ]);
    }
}