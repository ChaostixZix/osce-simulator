# Migration Guide: WorkOS to Supabase Authentication

## Executive Summary

Dokumen ini merangkum proses migrasi sistem otentikasi dari WorkOS ke Supabase pada project Laravel. Migrasi ini bertujuan untuk meningkatkan integrasi backend, memanfaatkan fitur database terpadu, dan menyederhanakan pengelolaan otentikasi.

## Current Implementation Analysis

### WorkOS Integration
- **Package**: `laravel/workos": "^0.1.0"`
- **Authentication Flow**: OAuth-based dengan redirect ke WorkOS
- **User Management**: Local user table dengan `workos_id` sebagai reference
- **Key Features**:
  - SSO dan social login
  - Session validation dengan WorkOS tokens
  - Automatic user syncing
  - Role-based access (admin/banned)

### Database Schema (Current)
```sql
-- users table
- id (primary key)
- email
- workos_id (unique)
- avatar
- is_admin (boolean)
- is_banned (boolean)
- timestamps
```

## Supabase vs WorkOS Comparison

### Similarities
| Fitur | WorkOS | Supabase |
|-------|--------|----------|
| OAuth Support | ✅ | ✅ |
| Social Login | ✅ | ✅ |
| User Management | ✅ | ✅ |
| JWT-based Auth | ✅ | ✅ |
| Role-based Access | ❌ | ✅ |

### Key Differences
| Aspek | WorkOS | Supabase |
|-------|--------|----------|
| Database Integration | Terpisah | Terintegrasi |
| Realtime Features | ❌ | ✅ |
| Built-in Storage | ❌ | ✅ |
| Row Level Security | ❌ | ✅ |
| Pricing Model | Per MAU | Gratis tier + Per MAU |
| Laravel Integration | Official Package | Community Packages |

### Advantages of Supabase
1. **All-in-one Solution**: Database + Auth + Storage + Realtime
2. **PostgreSQL Native**: Direct database access dengan RLS
3. **Open Source**: Full control atas infrastructure
4. **Rich Features**: Magic links, OTP, MFA, SAML
5. **Better Ecosystem**: JavaScript SDK yang lebih mature

### Challenges
1. **No Official Laravel Package**: Perlu custom implementation
2. **Enterprise Features**: WorkOS lebih unggul untuk enterprise SSO
3. **Migration Complexity**: Perlu data migration dan code changes

## Recommended Laravel Packages for Supabase

### Primary Option: `supabase-laravel`
```bash
composer require supabase-laravel/supabase-laravel
```
Fitur:
- Laravel service provider
- Auth guard integration
- Middleware untuk token validation
- Helper functions

### Alternative: Custom Implementation
Menggunakan `supabase-php` SDK dengan custom Laravel integration:
```php
// Basic usage
use Supabase\Supabase;

$client = Supabase::createClient(
    env('SUPABASE_URL'),
    env('SUPABASE_KEY')
);
```

## Migration Strategy

### Phase 1: Analysis & Planning (Week 1-2)
1. Setup Supabase project
2. Export existing users dari WorkOS
3. Design database schema dengan Supabase auth tables
4. Create migration plan

### Phase 2: Implementation (Week 3-4)
1. Install Supabase packages
2. Implement Supabase auth guard
3. Create middleware untuk session management
4. Update user model dan migrations
5. Import existing users ke Supabase

### Phase 3: Testing & Validation (Week 5)
1. End-to-end testing semua auth flows
2. Performance testing
3. Security validation
4. Fallback testing

### Phase 4: Deployment (Week 6)
1. Database migration
2. Code deployment
3. Monitor production
4. Rollback plan jika diperlukan

## Technical Implementation Details

### Database Schema Changes
```sql
-- Keep existing users table
ALTER TABLE users 
ADD COLUMN supabase_id UUID,
ADD COLUMN is_migrated BOOLEAN DEFAULT FALSE;

-- Supabase will create auth.users automatically
-- Create profile table linked to auth.users
CREATE TABLE profiles (
    id UUID REFERENCES auth.users(id) PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    avatar TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### Authentication Flow
1. **Login**: Redirect ke Supabase auth page
2. **Callback**: Handle JWT dari Supabase
3. **Session**: Store session di Laravel
4. **Validation**: Middleware validates Supabase JWT

### Migration Steps
1. **Export Users** dari WorkOS:
```php
$users = User::all()->map(function ($user) {
    return [
        'email' => $user->email,
        'workos_id' => $user->workos_id,
        'metadata' => [
            'local_id' => $user->id,
            'avatar' => $user->avatar
        ]
    ];
});
```

2. **Import ke Supabase** menggunakan custom migration script
3. **Update References** di local database
4. **Switch Auth Provider** di configuration

## Code Changes Required

### 1. Update .env
```env
# Remove WorkOS
# WORKOS_CLIENT_ID=
# WORKOS_API_KEY=
# WORKOS_REDIRECT_URL=

# Add Supabase
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

### 2. Update config/auth.php
```php
'guards' => [
    'web' => [
        'driver' => 'supabase',
        'provider' => 'users',
    ],
],
```

### 3. Create Supabase Auth Guard
```php
<?php

namespace App\Auth\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Supabase\Supabase;

class SupabaseGuard implements Guard
{
    use GuardHelpers;

    protected $supabase;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->supabase = Supabase::createClient(
            config('supabase.url'),
            config('supabase.key')
        );
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->request->bearerToken();

        if ($token) {
            $response = $this->supabase->auth->getUser($token);
            if ($response['data']) {
                $user = User::where('supabase_id', $response['data']['id'])->first();
            }
        }

        return $this->user = $user;
    }

    // ... other required methods
}
```

## Fallback Plan

1. **Database Backup**: Sebelum migrasi, backup lengkap database
2. **Feature Flag**: Implement feature flag untuk switching antara WorkOS/Supabase
3. **Rollback Script**: Script untuk revert perubahan jika error
4. **Monitoring**: Monitoring system untuk detect issues
5. **Gradual Rollout**: Migrate users bertahap (batch processing)

## Cost Analysis

### WorkOS
- $0 - 500 MAU/month
- $3 per MAU setelahnya
- Enterprise features additional cost

### Supabase
- Free tier: 500 MAU, 1GB database, 1GB storage
- Pro tier: $25/month + $0.015 per MAU
- Enterprise: Custom pricing

## Timeline & Resources

| Phase | Duration | Team Required |
|-------|----------|----------------|
| Planning | 2 weeks | 1 Dev + 1 PM |
| Implementation | 2 weeks | 2 Devs |
| Testing | 1 week | 1 QA + 1 Dev |
| Deployment | 1 week | 1 DevOps + 1 Dev |

## Recommendations

1. **Start with Proof of Concept**: Implement basic Supabase auth dulu
2. **Use Hybrid Approach**: Run both systems in parallel selama transisi
3. **Prioritize Features**: Migrate essential features dulu, advanced features later
4. **Document Everything**: Update documentation setiap perubahan
5. **Test Extensively**: E2E testing untuk semua auth flows

## Next Steps

1. Setup Supabase project
2. Install required packages
3. Create proof of concept
4. Review dengan team
5. Create detailed implementation plan

---
*Generated: 2025-09-24*
*Status: Draft*