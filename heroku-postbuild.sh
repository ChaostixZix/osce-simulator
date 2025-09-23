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

echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Post-build completed successfully!"
