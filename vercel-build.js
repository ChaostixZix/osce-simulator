#!/usr/bin/env node

const { execSync } = require('child_process');

console.log('Starting Vercel build process...');

try {
    // Change to webapp directory
    process.chdir('./webapp');
    
    console.log('Installing dependencies...');
    execSync('npm ci --production=false', { stdio: 'inherit' });
    
    console.log('Building assets...');
    execSync('npm run build', { stdio: 'inherit' });
    
    console.log('Build completed successfully!');
} catch (error) {
    console.error('Build failed:', error.message);
    process.exit(1);
}
