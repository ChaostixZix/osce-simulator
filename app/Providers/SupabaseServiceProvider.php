<?php

namespace App\Providers;

use App\Services\SupabaseService;
use Illuminate\Support\ServiceProvider;

class SupabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SupabaseService::class, function ($app) {
            return new SupabaseService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/supabase.php', 'services.supabase'
        );

        $this->publishes([
            __DIR__.'/../../config/supabase.php' => config_path('supabase.php'),
        ], 'supabase-config');
    }
}