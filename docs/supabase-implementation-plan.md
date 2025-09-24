# Implementation Plan: Supabase Auth Integration with Laravel

## Overview
Detail implementasi integrasi Supabase Auth dengan Laravel, menggantikan WorkOS sebagai provider otentikasi utama.

## Prerequisites

### System Requirements
- Laravel 10+
- PHP 8.1+
- Composer
- Node.js 18+ (untuk frontend)

### Supabase Setup
1. Create new project di [Supabase Dashboard](https://supabase.com/dashboard)
2. Catat credentials:
   - Project URL
   - Anon Key (public)
   - Service Role Key (secret)
3. Configure auth providers di Supabase:
   - Email/password
   - Social providers (Google, GitHub, etc.)
   - SAML jika diperlukan

## Phase 1: Package Installation & Configuration

### Step 1.1: Install Required Packages
```bash
# Install Supabase PHP SDK
composer require supabase/supabase-php

# Install JWT handler untuk token validation
composer require firebase/php-jwt

# Install HTTP client
composer require guzzlehttp/guzzle
```

### Step 1.2: Update Environment Variables
Tambahkan ke `.env`:
```env
# Supabase Configuration
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Auth Configuration
SUPABASE_JWT_SECRET=your-jwt-secret
SUPABASE_REDIRECT_URL=http://localhost:8000/auth/callback
```

### Step 1.3: Create Supabase Service Provider
```bash
php artisan make:provider SupabaseServiceProvider
```

File: `app/Providers/SupabaseServiceProvider.php`
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Supabase\Supabase;

class SupabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('supabase', function ($app) {
            return Supabase::createClient(
                config('supabase.url'),
                config('supabase.key')
            );
        });

        $this->app->singleton('supabase.admin', function ($app) {
            return Supabase::createClient(
                config('supabase.url'),
                config('supabase.service_role_key')
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/supabase.php' => config_path('supabase.php'),
        ], 'supabase-config');
    }
}
```

### Step 1.4: Create Configuration File
File: `config/supabase.php`
```php
<?php

return [
    'url' => env('SUPABASE_URL'),
    'key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    'jwt_secret' => env('SUPABASE_JWT_SECRET'),
    'redirect_url' => env('SUPABASE_REDIRECT_URL', env('APP_URL').'/auth/callback'),
    'token_expiry' => env('SUPABASE_TOKEN_EXPIRY', 3600),
];
```

## Phase 2: Database Migration

### Step 2.1: Update Users Table
Buat migration baru:
```bash
php artisan make:migration add_supabase_fields_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('supabase_id')->nullable()->unique()->after('id');
            $table->string('provider')->nullable()->after('email');
            $table->string('provider_id')->nullable()->after('provider');
            $table->boolean('is_migrated')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'supabase_id',
                'provider',
                'provider_id',
                'is_migrated',
                'last_login_at'
            ]);
        });
    }
};
```

### Step 2.2: Create Auth Sessions Table
```bash
php artisan make:migration create_supabase_sessions_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('supabase_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at');
            $table->json('user_metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supabase_sessions');
    }
};
```

## Phase 3: Authentication Implementation

### Step 3.1: Create Supabase Auth Guard
File: `app/Auth/SupabaseGuard.php`
```php
<?php

namespace App\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SupabaseGuard implements Guard
{
    use GuardHelpers;

    protected $request;
    protected $provider;
    protected $supabase;

    public function __construct(
        Request $request,
        UserProvider $provider
    ) {
        $this->request = $request;
        $this->provider = $provider;
        $this->supabase = app('supabase');
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        
        // Check for session first
        if ($this->request->hasSession()) {
            $userId = $this->request->session()->get('supabase_user_id');
            if ($userId) {
                $user = $this->provider->retrieveById($userId);
            }
        }

        // Check for Bearer token
        if (!$user && $this->request->bearerToken()) {
            $user = $this->validateToken($this->request->bearerToken());
        }

        return $this->user = $user;
    }

    protected function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(config('supabase.jwt_secret'), 'HS256'));
            
            if ($decoded->exp < time()) {
                return null;
            }

            return $this->provider->retrieveByCredentials([
                'supabase_id' => $decoded->sub
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validate(array $credentials = [])
    {
        return $this->attempt($credentials);
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        try {
            $response = $this->supabase->auth->signInWithPassword([
                'email' => $credentials['email'],
                'password' => $credentials['password']
            ]);

            if (isset($response['error'])) {
                return false;
            }

            $this->login($response);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function login($response)
    {
        $userData = $response['data']['user'];
        $session = $response['data']['session'];

        // Find or create user
        $user = $this->provider->retrieveByCredentials([
            'email' => $userData['email']
        ]);

        if (!$user) {
            $user = $this->provider->createModel();
            $user->email = $userData['email'];
            $user->supabase_id = $userData['id'];
            $user->save();
        }

        // Update user with Supabase data
        $user->supabase_id = $userData['id'];
        $user->last_login_at = now();
        $user->save();

        // Store session
        $this->request->session()->put('supabase_user_id', $user->id);
        $this->request->session()->put('supabase_access_token', $session['access_token']);
        $this->request->session()->put('supabase_refresh_token', $session['refresh_token']);

        $this->setUser($user);
    }

    public function logout()
    {
        $this->user = null;
        $this->request->session()->flush();
    }

    // ... implement other required methods
}
```

### Step 3.2: Create Auth Controller
```bash
php artisan make:controller Auth/SupabaseAuthController
```

File: `app/Http/Controllers/Auth/SupabaseAuthController.php`
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupabaseAuthController extends Controller
{
    protected $supabase;

    public function __construct()
    {
        $this->supabase = app('supabase');
    }

    public function login()
    {
        $providers = config('supabase.providers', []);
        
        return view('auth.login', compact('providers'));
    }

    public function redirectToProvider($provider)
    {
        $url = $this->supabase->auth->getOAuthProviderUrl($provider);
        
        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        try {
            // Handle OAuth callback
            if ($request->has('code')) {
                $response = $this->supabase->auth->exchangeCodeForSession($request->code);
                
                $userData = $response['data']['user'];
                
                // Find or create user
                $user = User::where('email', $userData['email'])->first();
                
                if (!$user) {
                    $user = User::create([
                        'email' => $userData['email'],
                        'supabase_id' => $userData['id'],
                        'provider' => $userData['app_metadata']['provider'],
                        'provider_id' => $userData['app_metadata']['provider_id'] ?? null,
                        'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                    ]);
                } else {
                    $user->update([
                        'supabase_id' => $userData['id'],
                        'last_login_at' => now(),
                    ]);
                }
                
                // Login user
                Auth::login($user);
                
                return redirect()->intended('/dashboard');
            }
            
            return redirect('/login')->withErrors(['message' => 'Authentication failed']);
            
        } catch (\Exception $e) {
            return redirect('/login')->withErrors([
                'message' => 'Authentication error: ' . $e->getMessage()
            ]);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $response = $this->supabase->auth->signUp([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'options' => [
                    'data' => [
                        'name' => $validated['name']
                    ]
                ]
            ]);

            if (isset($response['error'])) {
                return back()->withErrors(['email' => $response['error']['message']]);
            }

            // Create local user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'supabase_id' => $response['data']['user']['id'],
                'password' => bcrypt($validated['password']), // Keep for compatibility
            ]);

            return redirect('/login')->with('success', 'Registration successful! Please check your email.');
            
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        // Clear Supabase session
        $request->session()->flush();
        
        return redirect('/login');
    }
}
```

## Phase 4: Middleware & Routes

### Step 4.1: Create Middleware
```bash
php artisan make:middleware SupabaseAuthenticate
```

File: `app/Http/Middleware/SupabaseAuthenticate.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupabaseAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            return redirect('/login');
        }

        // Validate Supabase token
        $token = $request->session()->get('supabase_access_token');
        if ($token) {
            try {
                $supabase = app('supabase');
                $user = $supabase->auth->getUser($token);
                
                if (!isset($user['data'])) {
                    Auth::logout();
                    return redirect('/login');
                }
            } catch (\Exception $e) {
                // Try to refresh token
                $refreshToken = $request->session()->get('supabase_refresh_token');
                if ($refreshToken) {
                    try {
                        $response = $supabase->auth->refreshSession($refreshToken);
                        $request->session()->put('supabase_access_token', $response['access_token']);
                        $request->session()->put('supabase_refresh_token', $response['refresh_token']);
                    } catch (\Exception $e) {
                        Auth::logout();
                        return redirect('/login');
                    }
                } else {
                    Auth::logout();
                    return redirect('/login');
                }
            }
        }

        return $next($request);
    }
}
```

### Step 4.2: Update Routes
File: `routes/web.php`
```php
// Remove WorkOS routes
// require __DIR__.'/auth.php';

// Add Supabase routes
Route::get('/login', [SupabaseAuthController::class, 'login'])->name('login');
Route::get('/auth/{provider}', [SupabaseAuthController::class, 'redirectToProvider']);
Route::get('/auth/callback', [SupabaseAuthController::class, 'handleCallback']);
Route::post('/register', [SupabaseAuthController::class, 'register'])->name('register');
Route::post('/logout', [SupabaseAuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['supabase.auth', 'not-banned'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // ... other protected routes
});
```

### Step 4.3: Update Kernel.php
```php
protected $routeMiddleware = [
    'supabase.auth' => \App\Http\Middleware\SupabaseAuthenticate::class,
    // ... other middleware
];
```

## Phase 5: User Migration Script

### Step 5.1: Create Migration Command
```bash
php artisan make:command MigrateUsersToSupabase
```

File: `app/Console/Commands/MigrateUsersToSupabase.php`
```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Supabase\Supabase;

class MigrateUsersToSupabase extends Command
{
    protected $signature = 'supabase:migrate-users {--batch=100}';
    protected $description = 'Migrate existing users to Supabase';

    public function handle()
    {
        $batchSize = $this->option('batch');
        $supabase = app('supabase.admin');
        
        $users = User::where('is_migrated', false)
            ->take($batchSize)
            ->get();
            
        if ($users->isEmpty()) {
            $this->info('All users have been migrated.');
            return 0;
        }
        
        $this->info("Migrating {$users->count()} users...");
        
        foreach ($users as $user) {
            try {
                // Create user in Supabase
                $response = $supabase->auth->admin->createUser([
                    'email' => $user->email,
                    'password' => Hash::make(Str::random(16)), // Generate random password
                    'email_confirm' => true,
                    'user_metadata' => [
                        'migrated_from' => 'workos',
                        'local_id' => $user->id,
                        'avatar' => $user->avatar,
                        'is_admin' => $user->is_admin,
                    ]
                ]);
                
                // Update local user
                $user->update([
                    'supabase_id' => $response['id'],
                    'is_migrated' => true,
                ]);
                
                $this->line("Migrated user: {$user->email}");
                
            } catch (\Exception $e) {
                $this->error("Failed to migrate {$user->email}: {$e->getMessage()}");
            }
        }
        
        $this->info('Migration batch completed.');
        return 0;
    }
}
```

## Phase 6: Testing

### Test Scenarios
1. **User Registration**
2. **Email/Password Login**
3. **Social Login (Google, GitHub)**
4. **Token Refresh**
5. **Session Management**
6. **Logout**
7. **Password Reset**
8. **Protected Routes Access**

### Test Commands
```bash
# Run all tests
php artisan test

# Test auth specifically
php artisan test --filter=AuthTest

# Migrate test users
php artisan supabase:migrate-users --batch=10
```

## Phase 7: Deployment

### Pre-deployment Checklist
- [ ] Backup database
- [ ] Test in staging environment
- [ ] Update environment variables in production
- [ ] Run migrations
- [ ] Migrate users in batches
- [ ] Monitor error logs

### Deployment Steps
1. Deploy code changes
2. Run database migrations
3. Update environment variables
4. Migrate users:
```bash
php artisan supabase:migrate-users --batch=100
```
5. Monitor system performance
6. Enable new auth system

## Troubleshooting

### Common Issues
1. **JWT Validation Error**: Check JWT secret configuration
2. **Token Expired**: Implement automatic token refresh
3. **User Not Found**: Handle migration failures gracefully
4. **Rate Limiting**: Implement retry logic for API calls

### Monitoring
- Track auth success/failure rates
- Monitor API response times
- Log authentication errors
- Set up alerts for unusual activity

---
*Generated: 2025-09-24*
*Status: Implementation Plan*