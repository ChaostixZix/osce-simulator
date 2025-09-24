# Supabase Project Setup Guide

## 1. Create Supabase Project

1. Go to [https://supabase.com](https://supabase.com) and sign up/login
2. Click "New Project" 
3. Configure your project:
   - **Organization**: Select or create your organization
   - **Project Name**: `vibe-kanban` (or your preferred name)
   - **Database Password**: Generate a strong password
   - **Region**: Choose the region closest to your users
   - **Pricing Plan**: Free tier to start

## 2. Get Project Credentials

After project creation, go to Project Settings → API:

- **Project URL**: `https://your-project-id.supabase.co`
- **anon/public key**: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`
- **service_role key**: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`

## 3. Configure Authentication

Go to Authentication → Settings:

### URL Configuration
- **Site URL**: `http://localhost:8000` (for local dev)
- **Redirect URLs**: 
  - `http://localhost:8000/auth/supabase/callback`
  - `http://dev.bintangputra.my.id:8000/auth/supabase/callback`
  - `https://yourdomain.com/auth/supabase/callback`

### Providers Configuration
Enable the providers you need:
- **Email**: Enable (for passwordless login)
- **Google**: Enable (set up OAuth credentials)
- **GitHub**: Enable (set up OAuth credentials)
- Any other providers as needed

## 4. Database Setup

The database migrations will automatically create the necessary tables. Ensure your Laravel database connection is properly configured.

## 5. Environment Variables

Add these to your `.env` file:

```env
# Supabase Configuration
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Feature Flags
USE_SUPABASE_AUTH=false
SUPABASE_DUAL_MODE=true
```

## 6. Security Considerations

- Never expose the service_role key in client-side code
- Use Row Level Security (RLS) policies in Supabase
- Configure CORS settings properly
- Set up proper redirect URLs for OAuth providers

## Next Steps

After setting up the Supabase project, run:

```bash
# Install dependencies
composer require firebase/php-jwt guzzlehttp/guzzle
bun add @supabase/supabase-js

# Run migrations
php artisan migrate

# Test the setup
php artisan supabase:test
```