#!/usr/bin/env node

// Integration script to run the medical student image generator
// from the main project directory

import { spawn } from 'child_process';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

console.log('🎨 Medical Student Image Generator');
console.log('===================================\n');

// Change to the medical-student-images directory
const imageGeneratorDir = join(__dirname, 'medical-student-images');

console.log('📁 Changing to medical-student-images directory...\n');

// Run npm commands in the subdirectory
const runCommand = (command, args, options = {}) => {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd: imageGeneratorDir,
      stdio: 'inherit',
      shell: true,
      ...options
    });

    child.on('close', (code) => {
      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`Command failed with exit code ${code}`));
      }
    });

    child.on('error', reject);
  });
};

async function main() {
  try {
    // Install dependencies if node_modules doesn't exist
    console.log('📦 Installing dependencies...');
    await runCommand('npm', ['install']);
    
    // Generate images
    console.log('\n🎨 Generating medical student images...\n');
    await runCommand('npm', ['run', 'generate']);
    
    console.log('\n✅ Done! Check the medical-student-images/generated-images/ directory for your images.');
    
  } catch (error) {
    console.error('\n❌ Error:', error.message);
    process.exit(1);
  }
}

main();