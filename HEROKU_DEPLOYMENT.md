# Heroku Deployment Guide

## 1. Create Heroku App

```bash
heroku create osce-simulator
heroku buildpacks:set heroku/php
heroku buildpacks:add heroku/nodejs --index 1
```

## 2. Add Required Add-ons

```bash
heroku addons:create heroku-postgresql:essential-0
heroku addons:create heroku-redis:premium-0
```

## 3. Set Environment Variables

```bash
# Core Settings
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=base64:$(php artisan key:generate --show | cut -d' ' -f2)
heroku config:set APP_URL=https://osce-simulator.herokuapp.com

# Database (auto-set by Heroku)
heroku config:set DB_CONNECTION=pgsql

# Redis (auto-set by Heroku)
heroku config:set REDIS_CLIENT=phpredis
heroku config:set SESSION_DRIVER=redis
heroku config:set QUEUE_CONNECTION=redis
heroku config:set CACHE_STORE=redis

# S3 Storage
heroku config:set FILESYSTEM_DISK=s3
heroku config:set AWS_ACCESS_KEY_ID=AKIA2OAJTS22OVASA4PS
heroku config:set AWS_SECRET_ACCESS_KEY=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
heroku config:set AWS_DEFAULT_REGION=us-east-1
heroku config:set AWS_BUCKET=osce-simulator-uploads

# AI Services
heroku config:set AI_PROVIDER=gemini
heroku config:set GEMINI_API_KEY=your_gemini_key_here
heroku config:set GEMINI_MODEL=gemini-1.5-flash
heroku config:set USE_MULTI_PROMPT_ASSESSMENT=true

# Supabase Auth
heroku config:set SUPABASE_URL=https://your-project.supabase.co
heroku config:set SUPABASE_ANON_KEY=your_supabase_anon_key
heroku config:set SUPABASE_SERVICE_ROLE_KEY=your_supabase_service_role_key
heroku config:set SUPABASE_REDIRECT_URL=https://osce-simulator.herokuapp.com/auth/supabase/callback
heroku config:set SUPABASE_JWT_SECRET=your_supabase_jwt_secret

# OpenAI Azure (optional)
heroku config:set OPENAI_AZURE_API_KEY=
heroku config:set OPENAI_AZURE_ENDPOINT=
heroku config:set OPENAI_AZURE_DEPLOYMENT=
```

## 4. Deploy to Heroku

```bash
git add .
git commit -m "Configure for Heroku deployment"
git push heroku main
```

## 5. Post-Deployment Setup

```bash
# Run migrations
heroku run php artisan migrate --force

# Create storage link
heroku run php artisan storage:link

# Clear caches
heroku run php artisan config:clear
heroku run php artisan route:clear
heroku run php artisan view:clear

# Scale worker dyno
heroku ps:scale worker=1
```

## 6. Verify Deployment

```bash
# Check app status
heroku ps

# View logs
heroku logs --tail

# Test the application
heroku open
```

## 7. S3 Bucket Setup

1. Create S3 bucket named `osce-simulator-uploads` in AWS Console
2. Apply the bucket policy from `s3-bucket-policy.json`
3. Configure CORS if needed for uploads:
```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["*"],
        "ExposeHeaders": []
    }
]
```

## 8. Worker Dyno

The worker dyno will automatically process queued jobs for:
- Assessment generation
- Email notifications
- Other background tasks

## 9. Monitoring

```bash
# Monitor worker
heroku logs --ps worker --tail

# Check Redis
heroku redis:cli

# Check database
heroku pg:psql
```

## 10. Backup Strategy

Heroku automatically backs up the database daily. You can manually create backups:

```bash
heroku pg:backups:capture
```

## 11. Custom Domain (Optional)

```bash
heroku domains:add your-domain.com
# Update DNS settings as directed
```

## 12. SSL Certificate

Heroku provides automatic SSL termination with *.herokuapp.com certificates.
For custom domains, Heroku automatically manages Let's Encrypt certificates.
