<?php

use Illuminate\Support\Facades\Artisan;

test('appwrite migrate command fails gracefully when disabled', function (): void {
    config()->set('appwrite.enabled', false);

    $exitCode = Artisan::call('appwrite:migrate');

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('Appwrite integration is disabled');
});
