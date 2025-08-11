@echo off
REM Medical Training System Startup Script for Windows
REM This script helps users get started quickly with the application

echo 🏥 Medical Training System - Startup Script
echo ===========================================

REM Check if Node.js is installed
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Node.js is not installed. Please install Node.js version 16 or higher.
    echo    Visit: https://nodejs.org/
    pause
    exit /b 1
)

echo ✅ Node.js version: 
node --version

REM Check if dependencies are installed
if not exist "node_modules" (
    echo 📦 Installing dependencies...
    npm install
    if errorlevel 1 (
        echo ❌ Failed to install dependencies. Please check your npm installation.
        pause
        exit /b 1
    )
    echo ✅ Dependencies installed successfully
) else (
    echo ✅ Dependencies already installed
)

REM Check if .env file exists
if not exist ".env" (
    echo ⚠️  No .env file found. Creating template...
    (
        echo # OpenRouter API Configuration
        echo API_URL=https://openrouter.ai/api/v1/chat/completions
        echo API_KEY=your_openrouter_api_key_here
        echo API_MODEL=anthropic/claude-3.5-sonnet
        echo.
        echo # Replace 'your_openrouter_api_key_here' with your actual API key
        echo # Get your API key from: https://openrouter.ai/
    ) > .env
    echo 📝 Created .env template file
    echo ⚠️  Please edit .env file and add your OpenRouter API key before running the application
    echo    Get your API key from: https://openrouter.ai/
    echo.
    echo After adding your API key, run this script again or use: node app.js
    pause
    exit /b 0
)

REM Check if API key is configured
findstr /C:"your_openrouter_api_key_here" .env >nul
if not errorlevel 1 (
    echo ⚠️  Please configure your OpenRouter API key in the .env file
    echo    Edit .env and replace 'your_openrouter_api_key_here' with your actual API key
    echo    Get your API key from: https://openrouter.ai/
    pause
    exit /b 1
)

echo ✅ Configuration file found

REM Check if case files exist
if not exist "cases" (
    echo ❌ Case files directory not found. Please ensure the cases directory exists.
    pause
    exit /b 1
)

if not exist "cases\stemi-001.json" (
    echo ❌ Case files not found. Please ensure case files exist in the cases directory.
    pause
    exit /b 1
)

echo ✅ Case files found

REM Run tests to verify system health
echo 🧪 Running system health check...
npm test >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Some tests failed, but the application should still work
) else (
    echo ✅ System health check passed
)

echo.
echo 🚀 Starting Medical Training System...
echo 💡 Quick tips:
echo    • Type 'start osce' to begin medical case training
echo    • Type 'help' for detailed command reference
echo    • Type 'exit' to quit the application
echo.
echo Press Ctrl+C to stop the application
echo ===========================================
echo.

REM Start the application
node app.js

pause