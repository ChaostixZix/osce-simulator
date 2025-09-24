# WorkOS to Supabase Migration Guide

## Table of Contents
1. [Overview](#overview)
2. [Migration Strategy](#migration-strategy)
3. [Implementation Steps](#implementation-steps)
4. [Configuration](#configuration)
5. [Running the Migration](#running-the-migration)
6. [Testing the Migration](#testing-the-migration)
7. [Rollback Plan](#rollback-plan)
8. [Post-Migration Tasks](#post-migration-tasks)
9. [Troubleshooting](#troubleshooting)

## Overview

This guide documents the process of migrating authentication from WorkOS to Supabase in the Vibe Kanban application. The migration is designed to be seamless with zero downtime and allows for gradual transition using dual authentication mode.

## Migration Strategy

### Dual Authentication Mode
The migration implements a dual authentication system where:
- Non-migrated users continue using WorkOS authentication
- Migrated users use Supabase authentication
- The system automatically detects which auth method to use based on the user's `is_migrated` flag

### Migration Process
1. **Phase 1**: Setup Supabase project and install dependencies
2. **Phase 2**: Run database migrations to add Supabase fields
3. **Phase 3**: Enable dual authentication mode
4. **Phase 4**: Gradually migrate users using batch processing
5. **Phase 5**: Switch to Supabase-only mode once all users are migrated

## Implementation Steps

### 1. Set Up Supabase Project

1. Create a new Supabase project at [supabase.com](https://supabase.com)
2. Configure authentication providers (Google, GitHub, etc.)
3. Set up redirect URLs:
   - `http://localhost:8000/auth/supabase/callback`
   - `https://yourdomain.com/auth/supabase/callback`

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer require firebase/php-jwt guzzlehttp/guzzle

# Install frontend dependencies (if needed)
bun add @supabase/supabase-js
```

### 3. Update Environment Variables

Add these to your `.env` file:

```env
# Supabase Configuration
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
SUPABASE_REDIRECT_URL="${APP_URL}/auth/supabase/callback"

# Authentication Settings
USE_SUPABASE_AUTH=false
SUPABASE_DUAL_MODE=true
SUPABASE_AUTO_MIGRATE=false
```

### 4. Run Database Migrations

```bash
php artisan migrate
```

This will add the following columns to the `users` table:
- `supabase_id` (UUID)
- `provider` (string)
- `provider_id` (string)
- `is_migrated` (boolean)
- `last_login_at` (timestamp)

### 5. Configure Authentication

Update your route middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\SupabaseAuthenticate::class,
    ],
];
```

## Configuration

### Authentication Modes

1. **WorkOS Only (Default)**
   ```env
   USE_SUPABASE_AUTH=false
   SUPABASE_DUAL_MODE=false
   ```

2. **Dual Mode**
   ```env
   USE_SUPABASE_AUTH=false
   SUPABASE_DUAL_MODE=true
   ```

3. **Supabase Only**
   ```env
   USE_SUPABASE_AUTH=true
   SUPABASE_DUAL_MODE=false
   ```

### Route Configuration

The following routes are available for Supabase authentication:

- `/auth/supabase/login` - Login page
- `/auth/supabase/register` - Registration page
- `/auth/supabase/oauth/{provider}` - OAuth redirect
- `/auth/supabase/callback` - OAuth callback
- `/auth/supabase/logout` - Logout
- `/auth/supabase/migration-status` - Check migration status
- `/auth/supabase/migrate` - Migrate current user

## Running the Migration

### Command Options

```bash
# Dry run to see what would be migrated
php artisan supabase:migrate-users --dry-run

# Migrate users in batches of 100
php artisan supabase:migrate-users --batch=100

# Migrate specific users by email
php artisan supabase:migrate-users --email=user1@example.com --email=user2@example.com

# Force migration of already migrated users
php artisan supabase:migrate-users --force

# Start from a specific user ID
php artisan supabase:migrate-users --start=1000
```

### Best Practices

1. **Start with dry run**: Always run with `--dry-run` first to see what will happen
2. **Use small batches**: Start with small batch sizes (50-100 users)
3. **Monitor logs**: Check Laravel logs for any errors during migration
4. **Run during off-peak hours**: Schedule migrations during low traffic periods
5. **Have rollback ready**: Keep the fallback plan accessible

### Example Migration Process

```bash
# 1. Check how many users need migration
php artisan supabase:migrate-users --dry-run

# 2. Start with a small test batch
php artisan supabase:migrate-users --batch=10 --start=1

# 3. If successful, continue with larger batches
php artisan supabase:migrate-users --batch=100

# 4. Monitor progress
php artisan tinker
>>> App\Models\User::where('is_migrated', true)->count()
```

## Testing the Migration

### Unit Tests

Run the test suite to ensure everything works:

```bash
# Run all tests
php artisan test

# Run only Supabase tests
php artisan test --filter=Supabase

# Run migration command tests
php artisan test --filter=SupabaseMigrateUsers
```

### Manual Testing

1. **Test Authentication Flow**
   - Login with existing WorkOS users
   - Login with newly migrated Supabase users
   - Test OAuth providers
   - Test password reset flow

2. **Test Migration Endpoints**
   - Check migration status: `GET /auth/supabase/migration-status`
   - Migrate individual user: `POST /auth/supabase/migrate`

3. **Test Dual Mode**
   - Verify migrated users use Supabase
   - Verify non-migrated users still work with WorkOS

## Rollback Plan

### Immediate Rollback

If issues occur during migration:

1. **Disable Supabase Authentication**
   ```env
   USE_SUPABASE_AUTH=false
   SUPABASE_DUAL_MODE=false
   ```

2. **Clear Failed Migrations**
   ```sql
   UPDATE users SET is_migrated = false, supabase_id = NULL WHERE id IN (failed_user_ids);
   ```

3. **Restart Queue Workers**
   ```bash
   php artisan queue:restart
   ```

### Complete Rollback

1. **Switch back to WorkOS-only mode**
2. **Remove Supabase middleware**
3. **Delete migrated users from Supabase** (optional)

### Rollback Script

Create a rollback script at `scripts/rollback-supabase-migration.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\User;

// Reset migration status
User::query()->update([
    'is_migrated' => false,
    'supabase_id' => null,
    'provider' => null,
    'provider_id' => null,
]);

echo "Migration status has been reset.\n";
```

## Post-Migration Tasks

### 1. Clean Up

1. **Remove WorkOS Dependencies**
   ```bash
   composer remove laravel/workos
   ```

2. **Remove WorkOS Routes**
   - Remove WorkOS routes from `routes/auth.php`

3. **Update Views**
   - Update login/register forms to use Supabase endpoints

### 2. Enable Supabase Features

1. **Enable Row Level Security (RLS)**
   - Configure security policies in Supabase
   - Test data access rules

2. **Set Up Real-time Subscriptions**
   - Implement real-time features using Supabase Realtime

3. **Configure Storage**
   - Migrate file storage to Supabase Storage

### 3. Monitoring

1. **Monitor Authentication Metrics**
   - Login success rates
   - Token refresh failures
   - OAuth provider errors

2. **Set Up Alerts**
   - High failure rates
   - Unusual login patterns
   - API quota limits

## Troubleshooting

### Common Issues

1. **JWT Verification Failed**
   - Check Supabase JWT secret
   - Verify token format
   - Check system clock sync

2. **OAuth Callback Issues**
   - Verify redirect URL configuration
   - Check OAuth provider settings
   - Ensure CORS is configured

3. **Migration Command Fails**
   - Check database connection
   - Verify Supabase credentials
   - Check rate limits

4. **Session Issues**
   - Check session configuration
   - Verify CSRF protection
   - Check cookie settings

### Debug Commands

```bash
# Check current migration status
php artisan tinker
>>> App\Models\User::selectRaw('count(*) as total, sum(is_migrated) as migrated')->first()

# Test Supabase connection
php artisan tinker
>>> app(SupabaseService::class)->getUser('test-token')

# Clear authentication cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Log Locations

- **Laravel Logs**: `storage/logs/laravel.log`
- **Migration Logs**: Search for "Supabase" in Laravel logs
- **Nginx/Apache Logs**: Server error logs

## Support

For issues and questions:
1. Check this documentation
2. Review Supabase documentation
3. Check GitHub issues
4. Contact development team