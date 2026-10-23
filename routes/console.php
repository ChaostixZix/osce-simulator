<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('osce:prune-guests', function () {
    $count = \App\Models\OsceSession::whereNotNull('guest_token_hash')->where('created_at', '<', now()->subDays(7))->delete();
    $this->info("Removed {$count} expired guest encounters.");
})->purpose('Remove guest encounters older than seven days')->daily();
