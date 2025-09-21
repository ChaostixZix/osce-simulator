<?php

namespace App\Providers;

use App\Services\AppwriteService;
use Illuminate\Support\ServiceProvider;

class AppwriteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppwriteService::class, static fn (): AppwriteService => new AppwriteService());
    }

    public function boot(): void
    {
        //
    }
}
