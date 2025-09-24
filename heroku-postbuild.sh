#!/bin/bash

set -e

echo "Installing Node.js dependencies..."

# Check if bun is available, otherwise use npm
if command -v bun &> /dev/null; then
    echo "Using bun for package management..."
    bun install
    bun run build
else
    echo "Using npm for package management..."
    npm ci
    npm run build
fi

echo "Optimizing Laravel (runtime only)..."
# IMPORTANT: Do NOT cache config/routes at build time on Heroku.
# The runtime dyno environment provides DATABASE_URL and other vars that
# may differ from build-time, and caching here will bake wrong values
# (like DB_HOST=127.0.0.1) into bootstrap/cache/config.php.
# We'll clear caches at dyno boot in Procfile instead.
# php artisan config:cache
# php artisan route:cache
php artisan view:cache

echo "Post-build completed successfully!"
