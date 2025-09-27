#!/bin/bash

# Placeholder favicon generator script
# This script creates placeholder SVG favicons for development

echo "Creating placeholder favicons for Vibe Kanban..."

# Create SVG favicon
cat > /var/tmp/vibe-kanban/worktrees/e373-fix-metadata-for/public/favicon.svg << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect width="32" height="32" fill="#1e293b"/>
  <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="16" fill="white" text-anchor="middle" dominant-baseline="middle">VK</text>
</svg>
EOF

# Create ICO favicon (using convert from ImageMagick if available)
if command -v convert &> /dev/null; then
    convert /var/tmp/vibe-kanban/worktrees/e373-fix-metadata-for/public/favicon.svg /var/tmp/vibe-kanban/worktrees/e373-fix-metadata-for/public/favicon.ico
    echo "Created favicon.ico"
else
    echo "ImageMagick not found. Skipping ICO conversion."
fi

# Create apple-touch-icon.png
if command -v convert &> /dev/null; then
    convert -size 180x180 xc:#1e293b -font Arial -pointsize 72 -fill white -gravity center -annotate +0+0 "VK" /var/tmp/vibe-kanban/worktrees/e373-fix-metadata-for/public/apple-touch-icon.png
    echo "Created apple-touch-icon.png"
else
    echo "ImageMagick not found. Skipping PNG conversion."
fi

echo "Favicon placeholders created!"
echo "Remember to replace these with your actual brand assets."