<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use App\Models\User;

class WorkOSServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Extend the AuthKitAuthenticationRequest to handle WorkOS ID changes
        AuthKitAuthenticationRequest::macro('authenticateWithFallback', function () {
            return $this->authenticate(
                // Custom findUsing: try workos_id first, then email fallback
                findUsing: function (string $workosId) {
                    // First try to find by WorkOS ID
                    $user = User::where('workos_id', $workosId)->first();
                    
                    // If not found by WorkOS ID, we need to get the email from WorkOS
                    // and try to find an existing user by email
                    if (!$user) {
                        try {
                            \WorkOS\WorkOS::configure();
                            $workosUser = (new \WorkOS\UserManagement)->authenticateWithCode(
                                config('services.workos.client_id'),
                                request()->query('code'),
                            );
                            
                            // Try to find by email
                            $user = User::where('email', $workosUser->user->email)->first();
                        } catch (\Exception $e) {
                            // If WorkOS call fails, return null to create new user
                            return null;
                        }
                    }
                    
                    return $user;
                },
                // Custom updateUsing: always update the WorkOS ID
                updateUsing: function (User $user, \Laravel\WorkOS\User $userFromWorkOS) {
                    return tap($user)->update([
                        'name' => $userFromWorkOS->firstName.' '.$userFromWorkOS->lastName,
                        'workos_id' => $userFromWorkOS->id,
                        'avatar' => $userFromWorkOS->avatar ?? $user->avatar,
                    ]);
                }
            );
        });
    }
}