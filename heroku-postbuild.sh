#!/bin/bash

echo "Installing Node.js dependencies..."
npm ci

echo "Building frontend assets..."
npm run build

echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Post-build completed successfully!"
