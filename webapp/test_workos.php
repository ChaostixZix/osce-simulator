<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    Laravel\WorkOS\WorkOS::configure();
    echo "WorkOS configuration successful!\n";
    echo "Client ID: " . config('services.workos.client_id') . "\n";
    echo "API Key: " . (config('services.workos.secret') ? 'SET' : 'NOT SET') . "\n";
    echo "Redirect URL: " . config('services.workos.redirect_url') . "\n";
} catch (Exception $e) {
    echo "WorkOS configuration failed: " . $e->getMessage() . "\n";
}
