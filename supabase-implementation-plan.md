# Supabase Authentication Implementation Plan

## Overview

This document outlines the technical implementation details for migrating authentication from WorkOS to Supabase in the Vibe Kanban application.

## Architecture Overview

### Current Architecture
```
Frontend → WorkOS SDK → WorkOS Service → Laravel Auth
```

### Target Architecture
```
Frontend → Supabase Client → Supabase Service → Laravel Auth
```

### Migration Architecture
```
Frontend → [WorkOS SDK | Supabase Client] → [WorkOS | Supabase] → Laravel Auth (Dual Mode)
```

## Implementation Components

### 1. SupabaseService Class

**Location**: `app/Services/SupabaseService.php`

**Purpose**: Handles all communication with Supabase authentication API

**Key Methods**:
- `signInWithPassword()` - Email/password authentication
- `signUp()` - User registration
- `verifyToken()` - JWT token validation
- `refreshToken()` - Token refresh
- `createUser()` - Admin user creation
- `getUser()` - Get user data
- `updateUser()` - Update user profile
- `signOut()` - User logout
- `resetPasswordForEmail()` - Password reset
- `getOAuthUrl()` - OAuth provider URLs
- `exchangeCodeForSession()` - OAuth code exchange

### 2. SupabaseAuthenticate Middleware

**Location**: `app/Http/Middleware/SupabaseAuthenticate.php`

**Purpose**: Validates Supabase tokens and manages authentication sessions

**Features**:
- Dual mode support (WorkOS/Supabase)
- Automatic token refresh
- Session management
- Banned user detection
- Error handling and logging

### 3. SupabaseAuthController

**Location**: `app/Http/Controllers/Auth/SupabaseAuthController.php`

**Purpose**: Handles authentication routes and user actions

**Endpoints**:
- `GET /login` - Show login form
- `POST /login` - Process login
- `GET /register` - Show registration form
- `POST /register` - Process registration
- `GET /oauth/{provider}` - OAuth redirect
- `GET /callback` - OAuth callback
- `POST /logout` - Logout
- `GET /forgot-password` - Password reset form
- `POST /forgot-password` - Send reset email
- `POST /magic-link` - Magic link login
- `GET /migration-status` - Check migration status
- `POST /migrate` - Migrate current user

### 4. Database Schema

**Migration**: `database/migrations/2025_09_24_212707_add_supabase_fields_to_users_table.php`

**New Fields**:
- `supabase_id` - UUID from Supabase
- `provider` - Auth provider (email, google, github, etc.)
- `provider_id` - Provider-specific user ID
- `is_migrated` - Migration status flag
- `last_login_at` - Last login timestamp

### 5. Artisan Command

**Location**: `app/Console/Commands/SupabaseMigrateUsers.php`

**Purpose**: Batch migration of existing users

**Features**:
- Dry-run mode
- Batch processing
- Progress tracking
- Error handling
- Specific user migration
- Force migration option

### 6. Configuration Files

**Auth Configuration**: `config/auth.php`
- Added Supabase settings
- Feature flags for migration modes

**Services Configuration**: `config/services.php`
- Supabase API credentials
- OAuth provider settings

**Environment Variables**: `.env`
- `SUPABASE_URL` - Supabase project URL
- `SUPABASE_ANON_KEY` - Anonymous API key
- `SUPABASE_SERVICE_ROLE_KEY` - Service role key
- `USE_SUPABASE_AUTH` - Enable Supabase auth
- `SUPABASE_DUAL_MODE` - Enable dual authentication

### 7. Service Provider

**Location**: `app/Providers/SupabaseAuthProvider.php`

**Purpose**: Bootstrap authentication configuration

**Features**:
- Sets auth mode based on environment
- Shares auth mode with views
- Handles configuration updates

## Implementation Phases

### Phase 1: Preparation (Week 1)

1. **Set up Supabase Project**
   - Create project
   - Configure auth providers
   - Set up redirect URLs
   - Generate API keys

2. **Install Dependencies**
   ```bash
   composer require firebase/php-jwt guzzlehttp/guzzle
   ```

3. **Update Environment**
   - Add Supabase credentials
   - Set feature flags
   - Test configuration

### Phase 2: Core Implementation (Week 2)

1. **Database Migration**
   ```bash
   php artisan migrate
   ```

2. **Implement Services**
   - SupabaseService
   - Middleware
   - Controller
   - Service Provider

3. **Add Routes**
   - Update `routes/auth.php`
   - Test all endpoints

### Phase 3: Testing (Week 3)

1. **Unit Tests**
   - SupabaseService tests
   - Middleware tests
   - Controller tests

2. **Integration Tests**
   - Full authentication flow
   - OAuth providers
   - Error scenarios

3. **Migration Command Tests**
   - Dry run tests
   - Batch processing
   - Error handling

### Phase 4: Migration (Week 4)

1. **Enable Dual Mode**
   ```env
   USE_SUPABASE_AUTH=false
   SUPABASE_DUAL_MODE=true
   ```

2. **Run Migration**
   ```bash
   php artisan supabase:migrate-users --dry-run
   php artisan supabase:migrate-users --batch=100
   ```

3. **Monitor and Validate**
   - Check logs
   - Monitor performance
   - Validate user access

### Phase 5: Cutover (Week 5)

1. **Enable Supabase-Only Mode**
   ```env
   USE_SUPABASE_AUTH=true
   SUPABASE_DUAL_MODE=false
   ```

2. **Remove WorkOS**
   ```bash
   composer remove laravel/workos
   ```

3. **Cleanup**
   - Remove WorkOS routes
   - Update views
   - Archive old code

## Code Examples

### User Authentication Flow

```php
// Login
$response = $supabase->signInWithPassword([
    'email' => $request->email,
    'password' => $request->password,
]);

// Store session
session([
    'supabase_access_token' => $response['session']['access_token'],
    'supabase_refresh_token' => $response['session']['refresh_token'],
]);

// Login user
Auth::login($user);
```

### Middleware Logic

```php
// Check authentication mode
if ($dualMode && $user->isMigrated()) {
    // Use Supabase auth
    $this->handleSupabaseAuth($request, $next);
} else if ($useSupabaseAuth) {
    // Force Supabase auth
    $this->handleSupabaseAuth($request, $next);
} else {
    // Use Laravel auth
    return $next($request);
}
```

### Migration Command Usage

```bash
# Test migration
php artisan supabase:migrate-users --dry-run

# Migrate in batches
php artisan supabase:migrate-users --batch=50

# Migrate specific users
php artisan supabase:migrate-users --email=user@example.com

# Monitor progress
php artisan tinker
>>> \App\Models\User::where('is_migrated', true)->count()
```

## Security Considerations

### 1. Token Security
- Store tokens in secure HTTP-only cookies
- Implement proper token expiration
- Use HTTPS for all authentication requests
- Validate tokens on each request

### 2. Data Protection
- Never expose service role key
- Use environment variables for secrets
- Implement proper CORS policies
- Validate all user inputs

### 3. Migration Safety
- Run migrations in transactions
- Backup database before migration
- Use dry-run mode first
- Keep detailed migration logs

## Performance Optimization

### 1. Caching
- Cache JWT validation results
- Cache user metadata
- Use Redis for session storage

### 2. Batch Processing
- Process users in small batches
- Add delays between batches
- Monitor memory usage

### 3. Error Handling
- Implement retry logic for API calls
- Graceful degradation on failures
- Detailed error logging

## Monitoring and Logging

### 1. Key Metrics
- Authentication success rate
- Token refresh frequency
- API response times
- Error rates by type

### 2. Log Events
- Successful logins
- Failed authentications
- Token refresh events
- Migration progress

### 3. Alerts
- High failure rates
- API quota limits
- Unusual login patterns
- System errors

## Testing Strategy

### 1. Unit Tests
- Test all service methods
- Mock external API calls
- Test error scenarios

### 2. Integration Tests
- Full authentication flow
- OAuth provider integration
- Session management

### 3. Migration Tests
- Test command functionality
- Validate data integrity
- Test rollback procedures

### 4. Load Tests
- Simulate concurrent users
- Test under heavy load
- Monitor performance metrics

## Rollback Strategy

### 1. Immediate Actions
```bash
# Disable Supabase
sed -i 's/USE_SUPABASE_AUTH=.*/USE_SUPABASE_AUTH=false/' .env
sed -i 's/SUPABASE_DUAL_MODE=.*/SUPABASE_DUAL_MODE=false/' .env

# Clear cache
php artisan cache:clear
```

### 2. Database Reset
```php
// Reset migration status
User::query()->update([
    'is_migrated' => false,
    'supabase_id' => null,
]);
```

### 3. Restore from Backup
```bash
# Restore database
mysql -u user -p database < backup.sql

# Restore environment
cp .env.backup .env
```

## Deployment Strategy

### 1. Staging Deployment
- Deploy to staging environment
- Test all features
- Validate configuration
- Run migration test

### 2. Production Deployment
- Deploy during maintenance window
- Run database migrations
- Enable dual mode
- Monitor closely

### 3. Validation
- Test all authentication methods
- Verify user access
- Check performance metrics
- Validate error logs

## Success Criteria

### 1. Technical Metrics
- Authentication success rate > 99.9%
- Average response time < 500ms
- Zero data loss during migration
- All users successfully migrated

### 2. User Experience
- Seamless transition for users
- No login interruptions
- All features working as expected
- Clear error messages

### 3. Operational Metrics
- Monitoring in place
- Alerting configured
- Documentation complete
- Team trained on new system

## Next Steps

1. Review this implementation plan
2. Set up staging environment
3. Begin Phase 1 implementation
4. Schedule migration timeline
5. Prepare communication plan

---

This implementation plan provides a comprehensive guide for successfully migrating from WorkOS to Supabase authentication while ensuring minimal disruption to users.