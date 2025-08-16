# Heroku Deployment Guide for OSC Medical Training App

This guide will help you deploy your Laravel + Vue + Inertia.js application to Heroku.

## Prerequisites

1. [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli) installed
2. Git repository initialized
3. Heroku account

## Step 1: Login to Heroku

```bash
heroku login
```

## Step 2: Create Heroku App

```bash
cd webapp
heroku create your-app-name
```

Replace `your-app-name` with your desired app name.

## Step 3: Add PostgreSQL Database

```bash
heroku addons:create heroku-postgresql:essential-0
```

## Step 4: Set Environment Variables

```bash
# Set Laravel app key (generate one first if you don't have it)
php artisan key:generate --show
heroku config:set APP_KEY="base64:your-generated-key-here"

# Set basic environment variables
heroku config:set APP_NAME="OSC Medical Training"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_URL=https://your-app-name.herokuapp.com
heroku config:set LOG_CHANNEL=stderr
heroku config:set LOG_LEVEL=info

# Session and cache settings
heroku config:set SESSION_DRIVER=database
heroku config:set CACHE_STORE=database
heroku config:set QUEUE_CONNECTION=database

# The database URL will be automatically set by the PostgreSQL addon
# heroku config:set DATABASE_URL will be done automatically

# Optional: Set your other environment variables as needed
heroku config:set MAIL_MAILER=log
heroku config:set VITE_APP_NAME="OSC Medical Training"
```

## Step 5: Configure Buildpacks

```bash
# Add Node.js buildpack for frontend assets
heroku buildpacks:add heroku/nodejs

# Add PHP buildpack for Laravel
heroku buildpacks:add heroku/php
```

## Step 6: Deploy to Heroku

```bash
# Add all files to git
git add .
git commit -m "Prepare for Heroku deployment"

# Deploy to Heroku
git push heroku main
```

If you're on a different branch (like `master`), use:
```bash
git push heroku master:main
```

## Step 7: Run Initial Migration

```bash
heroku run php artisan migrate --force
```

## Step 8: View Your App

```bash
heroku open
```

## Troubleshooting

### View Logs
```bash
heroku logs --tail
```

### Run Artisan Commands
```bash
heroku run php artisan migrate --force
heroku run php artisan config:cache
heroku run php artisan route:cache
heroku run php artisan view:cache
```

### Clear Cache
```bash
heroku run php artisan config:clear
heroku run php artisan route:clear
heroku run php artisan view:clear
```

### Check Environment Variables
```bash
heroku config
```

### Database Access
```bash
heroku pg:psql
```

## Important Files Created for Deployment

1. **Procfile** - Tells Heroku how to run your app
2. **heroku-postbuild.sh** - Builds frontend assets and optimizes Laravel
3. **Updated composer.json** - Added PostgreSQL extension and build scripts
4. **Updated package.json** - Added heroku-postbuild script
5. **Updated database.php** - Uses PostgreSQL in production

## Post-Deployment Checklist

- [ ] App loads successfully
- [ ] Database migrations ran successfully
- [ ] Frontend assets are building correctly
- [ ] All environment variables are set
- [ ] SSL is working (Heroku provides this automatically)

## Updating Your App

For future deployments:

```bash
git add .
git commit -m "Your commit message"
git push heroku main
```

The app will automatically rebuild and deploy!

## Performance Optimization

Consider adding these addons for better performance:

```bash
# Redis for caching (optional, paid)
heroku addons:create heroku-redis:premium-0

# Then update your environment:
heroku config:set CACHE_STORE=redis
heroku config:set SESSION_DRIVER=redis
heroku config:set QUEUE_CONNECTION=redis
```
