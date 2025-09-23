<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use App\Models\User;
use WorkOS\WorkOS as WorkOSSDK;

class WorkOSServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure WorkOS SDK with API key
        $this->configureWorkOS();

        // Extend the AuthKitAuthenticationRequest with an email-safe flow
        AuthKitAuthenticationRequest::macro('authenticateWithFallback', function () {
            return $this->authenticate(
                // 1) Find existing user by WorkOS ID when present
                findUsing: function (string $workosId) {
                    return User::where('workos_id', $workosId)->first();
                },
                // 2) Create or link by email to avoid UNIQUE email conflicts
                createUsing: function (\Laravel\WorkOS\User $workosUser) {
                    $fullName = trim(($workosUser->firstName ?? '').' '.($workosUser->lastName ?? ''));
                    return User::updateOrCreate(
                        ['email' => $workosUser->email],
                        [
                            'name' => $fullName !== '' ? $fullName : ($workosUser->email ?? 'User'),
                            'workos_id' => $workosUser->id,
                            'avatar' => $workosUser->avatar ?? null,
                        ]
                    );
                },
                // 3) Always keep local user in sync with WorkOS profile
                updateUsing: function (User $user, \Laravel\WorkOS\User $workosUser) {
                    $fullName = trim(($workosUser->firstName ?? '').' '.($workosUser->lastName ?? ''));
                    return tap($user)->update([
                        'name' => $fullName !== '' ? $fullName : ($user->name ?? $workosUser->email),
                        'workos_id' => $workosUser->id,
                        'avatar' => $workosUser->avatar ?? $user->avatar,
                    ]);
                }
            );
        });
    }

    /**
     * Configure WorkOS SDK with proper API key
     */
    protected function configureWorkOS(): void
    {
        $apiKey = config('services.workos.secret') ?: env('WORKOS_API_KEY');
        
        if ($apiKey) {
            WorkOSSDK::setApiKey($apiKey);
            
            // Also set client ID if available
            $clientId = config('services.workos.client_id');
            if ($clientId) {
                WorkOSSDK::setClientId($clientId);
            }
        }
    }
}
