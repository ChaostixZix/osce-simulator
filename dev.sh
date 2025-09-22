#!/bin/bash

# Development script for the OSCE Simulator
# This script starts the full development stack including:
# - PHP server
# - Queue worker
# - WebSocket/Reverb server
# - Vite development server (via bun)

echo "🚀 Starting OSCE Simulator development environment..."

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP first."
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if bun is installed
if ! command -v bun &> /dev/null; then
    echo "❌ bun is not installed. Please install bun first."
    echo "Visit: https://bun.sh/"
    exit 1
fi

# Install PHP dependencies if vendor folder doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies..."
    composer install
fi

# Install JS dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "📦 Installing JavaScript dependencies..."
    bun install
fi

# Start PHP server in background
echo "🌐 Starting PHP server on port 8000..."
php artisan serve --host=0.0.0.0 --port=8000 &
PHP_PID=$!

# Start queue worker in background
echo "🔄 Starting queue worker..."
php artisan queue:work &
QUEUE_PID=$!

# Start Reverb server in background
echo "🔌 Starting WebSocket server..."
php artisan reverb:start --host=0.0.0.0 --port=8082 &
REVERB_PID=$!

# Start Vite dev server
echo "⚡ Starting Vite development server..."
bun run dev &
VITE_PID=$!

# Function to cleanup on exit
cleanup() {
    echo ""
    echo "🛑 Stopping all services..."
    kill $PHP_PID 2>/dev/null
    kill $QUEUE_PID 2>/dev/null
    kill $REVERB_PID 2>/dev/null
    kill $VITE_PID 2>/dev/null
    echo "✅ All services stopped"
    exit 0
}

# Set trap to cleanup on script termination
trap cleanup SIGINT SIGTERM

echo ""
echo "✅ All services started successfully!"
echo "📍 Application is running at: http://localhost:8000"
echo "📡 Vite dev server is running"
echo "💬 WebSocket server is running"
echo ""
echo "Press Ctrl+C to stop all services"

# Wait for all background processes
wait