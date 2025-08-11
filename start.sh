#!/bin/bash

# Medical Training System Startup Script
# This script helps users get started quickly with the application

echo "🏥 Medical Training System - Startup Script"
echo "==========================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js version 16 or higher."
    echo "   Visit: https://nodejs.org/"
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 16 ]; then
    echo "❌ Node.js version 16 or higher is required. Current version: $(node -v)"
    echo "   Please update Node.js: https://nodejs.org/"
    exit 1
fi

echo "✅ Node.js version: $(node -v)"

# Check if dependencies are installed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
    if [ $? -ne 0 ]; then
        echo "❌ Failed to install dependencies. Please check your npm installation."
        exit 1
    fi
    echo "✅ Dependencies installed successfully"
else
    echo "✅ Dependencies already installed"
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "⚠️  No .env file found. Creating template..."
    cat > .env << EOF
# OpenRouter API Configuration
API_URL=https://openrouter.ai/api/v1/chat/completions
API_KEY=your_openrouter_api_key_here
API_MODEL=anthropic/claude-3.5-sonnet

# Replace 'your_openrouter_api_key_here' with your actual API key
# Get your API key from: https://openrouter.ai/
EOF
    echo "📝 Created .env template file"
    echo "⚠️  Please edit .env file and add your OpenRouter API key before running the application"
    echo "   Get your API key from: https://openrouter.ai/"
    echo ""
    echo "After adding your API key, run this script again or use: node app.js"
    exit 0
fi

# Check if API key is configured
if grep -q "your_openrouter_api_key_here" .env; then
    echo "⚠️  Please configure your OpenRouter API key in the .env file"
    echo "   Edit .env and replace 'your_openrouter_api_key_here' with your actual API key"
    echo "   Get your API key from: https://openrouter.ai/"
    exit 1
fi

echo "✅ Configuration file found"

# Check if case files exist
if [ ! -d "cases" ] || [ ! -f "cases/stemi-001.json" ]; then
    echo "❌ Case files not found. Please ensure the cases directory exists with case files."
    exit 1
fi

echo "✅ Case files found"

# Run tests to verify system health
echo "🧪 Running system health check..."
npm test > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ System health check passed"
else
    echo "⚠️  Some tests failed, but the application should still work"
fi

echo ""
echo "🚀 Starting Medical Training System..."
echo "💡 Quick tips:"
echo "   • Type 'start osce' to begin medical case training"
echo "   • Type 'help' for detailed command reference"
echo "   • Type 'exit' to quit the application"
echo ""
echo "Press Ctrl+C to stop the application"
echo "==========================================="
echo ""

# Start the application
node app.js