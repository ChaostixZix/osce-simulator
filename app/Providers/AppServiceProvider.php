<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Separate buckets keep history-taking from consuming submission attempts.
        foreach (['start' => 10, 'message' => 60, 'action' => 30, 'submit' => 10] as $action => $limit) {
            RateLimiter::for('demo-'.$action, fn (Request $request) => Limit::perMinute($limit)->by($request->ip()));
        }
    }
}
