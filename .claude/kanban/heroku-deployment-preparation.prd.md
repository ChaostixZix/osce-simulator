# Heroku Deployment Preparation for OSCE Simulator

## Project Overview
OSCE Simulator adalah aplikasi Laravel + React + Inertia untuk simulasi ujian medis dengan fitur:
- AI Patient Service (Gemini/OpenAI)
- Real-time assessment dengan Reverb
- Queue processing untuk background jobs
- PostgreSQL database
- Redis untuk cache dan session

## Deployment Checklist

### 1. 🚨 Security Issues (CRITICAL - Handle Immediately)
- [ ] Remove Azure OpenAI API key from `.env.example` (line 102-104)
- [ ] Generate new APP_KEY for production
- [ ] Ensure all sensitive keys are properly set in Heroku config vars

### 2. Heroku App Setup
- [ ] Create new Heroku app: `heroku create your-app-name`
- [ ] Add Heroku PostgreSQL add-on: `heroku addons:create heroku-postgresql:standard-0`
- [ ] Add Heroku Redis add-on: `heroku addons:create heroku-redis:premium-0`
- [ ] Add Papertrail add-on for logging: `heroku addons:create papertrail`

### 3. Environment Variables Configuration
Set these config vars in Heroku:

```bash
# Core App Settings
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=<generate_new_key>
heroku config:set APP_URL=https://your-app.herokuapp.com
heroku config:set APP_LOCALE=en
heroku config:set APP_FALLBACK_LOCALE=en

# Database (Heroku provides DATABASE_URL automatically)
heroku config:set DB_CONNECTION=pgsql

# Redis (Heroku provides REDIS_URL automatically)
heroku config:set REDIS_CLIENT=phpredis
heroku config:set SESSION_DRIVER=redis
heroku config:set QUEUE_CONNECTION=redis
heroku config:set CACHE_STORE=redis

# File Storage (Configure AWS S3)
heroku config:set FILESYSTEM_DISK=s3
heroku config:set AWS_ACCESS_KEY_ID=<your_aws_key>
heroku config:set AWS_SECRET_ACCESS_KEY=<your_aws_secret>
heroku config:set AWS_DEFAULT_REGION=us-east-1
heroku config:set AWS_BUCKET=<your_bucket_name>

# AI Services
heroku config:set AI_PROVIDER=gemini
heroku config:set GEMINI_API_KEY=<your_gemini_api_key>
heroku config:set GEMINI_MODEL=gemini-1.5-flash
heroku config:set USE_MULTI_PROMPT_ASSESSMENT=true

# WorkOS Authentication
heroku config:set WORKOS_CLIENT_ID=<your_workos_client_id>
heroku config:set WORKOS_API_KEY=<your_workos_api_key>
heroku config:set WORKOS_REDIRECT_URL=https://your-app.herokuapp.com/authenticate

# Mail Configuration (Optional)
heroku config:set MAIL_MAILER=smtp
heroku config:set MAIL_HOST=smtp.sendgrid.net
heroku config:set MAIL_PORT=587
heroku config:set MAIL_USERNAME=apikey
heroku config:set MAIL_PASSWORD=<sendgrid_api_key>
heroku config:set MAIL_ENCRYPTION=tls
heroku config:set MAIL_FROM_ADDRESS=noreply@yourapp.com
```

### 4. Buildpack Configuration
```bash
# Set buildpacks in correct order
heroku buildpacks:set heroku/php
heroku buildpacks:set heroku/nodejs

# Set Node.js version (package.json already specifies 20.x)
heroku config:set NODE_ENV=production
```

### 5. Procfile Configuration
Create `Procfile` in root:
```
web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:work --queue=assessments,management,default --tries=3 --sleep=1 --max-time=3600
```

### 6. File Updates Required

#### a) Update `.env.example`
Remove line 102-104:
```env
# Remove these lines:
OPENAI_AZURE_API_KEY=9JE2VT8FkPIipTzgg49xvDWaGtTttaPIOvpVTa3tjf5UUtGQeHhzJQQJ99BIACHYHv6XJ3w3AAAAACOGFc4p
OPENAI_AZURE_ENDPOINT=https://zixmc-mf88hoye-eastus2.openai.azure.com/openai/v1/
OPENAI_AZURE_DEPLOYMENT=gpt-4.1-nano
```

#### b) Create `app/Providers/AppServiceProvider.php` update
```php
public function boot()
{
    // Force HTTPS in production
    if (env('APP_ENV') === 'production') {
        URL::forceScheme('https');
    }
    
    // Set proper storage path for Heroku
    $this->app->useStoragePath(env('APP_STORAGE_PATH', base_path('storage')));
}
```

#### c) Update `config/filesystems.php`
Ensure S3 configuration is proper for production:
```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
],
```

### 7. Deployment Commands
```bash
# Deploy to Heroku
git add .
git commit -m "Prepare for Heroku deployment"
git push heroku main

# Run migrations after deployment
heroku run php artisan migrate --force

# Clear caches
heroku run php artisan config:clear
heroku run php artisan route:clear
heroku run php artisan view:clear
heroku run php artisan cache:clear

# Start queue worker
heroku ps:scale worker=1
```

### 8. Post-Deployment Checks
- [ ] Verify app is accessible at https://your-app.herokuapp.com
- [ ] Test user authentication flow
- [ ] Test AI patient service functionality
- [ ] Verify file uploads work with S3
- [ ] Check Heroku logs: `heroku logs --tail`
- [ ] Monitor queue processing: `heroku logs --ps worker`
- [ ] Verify Redis connections for sessions and cache

### 9. Monitoring and Maintenance
- [ ] Set up error monitoring (Sentry or similar)
- [ ] Configure daily database backups
- [ ] Set up uptime monitoring
- [ ] Monitor Heroku dyno usage and scale as needed
- [ ] Regularly check Heroku add-on usage and costs

### 10. Known Issues and Solutions

#### Issue 1: Build Failures
- **Symptom**: Node.js or PHP build errors
- **Solution**: Check build logs, ensure all dependencies are compatible

#### Issue 2: Database Connection
- **Symptom**: Application can't connect to PostgreSQL
- **Solution**: Verify DATABASE_URL is properly set by Heroku

#### Issue 3: Redis Connection
- **Symptom**: Session or cache not working
- **Solution**: Verify REDIS_URL and switch to phpredis client

#### Issue 4: File Upload Issues
- **Symptom**: Files not saving or not accessible
- **Solution**: Configure S3 properly and ensure IAM permissions

#### Issue 5: Queue Jobs Not Processing
- **Symptom**: Background jobs stuck
- **Solution**: Check worker dyno logs, ensure queue:work is running

### 11. Cost Considerations
- Heroku PostgreSQL: $9/month (standard-0)
- Heroku Redis: $15/month (premium-0)
- Web Dyno: $7/month (hobby tier)
- Worker Dyno: $7/month (hobby tier)
- Estimated monthly cost: ~$38+ before add-ons

### 12. Backup Strategy
- Enable automated daily backups for PostgreSQL
- Implement regular S3 backups for user uploads
- Keep configuration backups locally

## Success Criteria
- [ ] Application loads successfully on Heroku
- [ ] All authentication flows work
- [ ] AI patient service functions correctly
- [ ] File uploads and downloads work
- [ ] Queue jobs process without errors
- [ ] Real-time features (Reverb) work properly
- [ ] Application performs well under load