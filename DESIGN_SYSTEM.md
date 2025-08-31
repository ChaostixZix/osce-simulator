# OSCE Gaming Design System

**Version:** 2.1  
**Framework:** React + Inertia + Tailwind CSS  
**Aesthetic:** Cyber/Gaming with Techy Elements  

## 🎨 Design Philosophy

- **Gaming Aesthetic**: Cyber-punk inspired with neon accents and sharp angles
- **Techy Feel**: Monospace fonts, terminal-style interfaces, system indicators  
- **High Readability**: Strong contrast ratios, clear text hierarchy
- **Smooth Interactions**: 300ms transitions, hover effects, loading states
- **Consistent Colors**: Emerald/cyan primary palette with accent colors

## 🎮 Core Components

### 1. **Cyber Borders** (`cyber-border`)
```css
/* Sharp angled corners - NO rectangles allowed */
clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 12px 100%, 0 calc(100% - 12px));
```
**Usage:** All cards, containers, buttons  
**Rule:** NEVER use regular rectangles - always use angled corners

### 2. **Cyber Buttons** (`cyber-button`) 
```jsx
<button className="cyber-button px-4 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide">
  Button Text
</button>
```
**Features:**
- Angled corners with cyber-border
- Monospace font (`font-mono`)
- Uppercase text (`uppercase`)  
- Wide letter spacing (`tracking-wide`)
- Hover scale effect (1.02x)

### 3. **Color Palette**

#### Primary Colors
- **Emerald**: `emerald-400`, `emerald-500`, `emerald-600`
- **Cyan**: `cyan-400`, `cyan-500` 
- **Background**: `bg-white dark:bg-neutral-950`
- **Text**: `text-foreground`, `text-muted-foreground`

#### Accent Colors (for variety)
- **Blue**: `blue-400`, `blue-500` 
- **Purple**: `purple-400`, `purple-500`
- **Red**: `red-400`, `red-500` (for errors/warnings)

### 4. **Typography Hierarchy**

```jsx
/* Page Title */
<h1 className="text-3xl font-medium lowercase glow-text text-foreground">

/* Section Title */  
<h2 className="text-lg font-medium lowercase text-foreground font-mono">

/* Card Title */
<h3 className="text-sm font-semibold lowercase text-foreground">

/* Body Text */
<p className="text-muted-foreground lowercase">

/* Small Text/Captions */
<span className="text-xs text-muted-foreground font-mono uppercase tracking-wider">
```

**Rules:**
- Always use `lowercase` for titles and content
- Use `font-mono` for technical/system text
- Use `uppercase tracking-wider` for labels and status indicators

### 5. **Interactive Elements**

#### Hover Effects
```jsx
/* Scale hover for cards */
hover:scale-[1.02] transition-all duration-300

/* Glow hover for buttons */  
hover:shadow-lg hover:shadow-emerald-500/20

/* Color transition */
transition-colors duration-300
```

#### Loading States
```jsx
/* Spinner */
<div className="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>

/* Pulsing dot */
<div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
```

## 🎯 Layout Patterns

### 1. **Page Structure**
```jsx
<div className="space-y-8">
  {/* Welcome Header */}
  <div className="text-center space-y-4 relative">
    <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
    
    <div className="flex items-center justify-center gap-3">
      <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
      <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">SECTION LABEL</span>
      <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
    </div>
    
    <h1 className="text-2xl font-medium lowercase glow-text text-foreground">Page Title</h1>
  </div>
  
  {/* Content sections... */}
</div>
```

### 2. **Section Headers**
```jsx
<div className="flex items-center gap-3 mb-4">
  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
  <h2 className="text-lg font-medium lowercase text-foreground font-mono">section title</h2>
  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
  <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
    <span>status info</span>
  </div>
</div>
```

### 3. **Card Grid**
```jsx
<div className="grid md:grid-cols-3 gap-6">
  {items.map((item, idx) => {
    const colors = [
      { bg: 'bg-gradient-to-br from-emerald-500/10 to-emerald-600/5', border: 'border-emerald-500/30', accent: 'text-emerald-400' },
      { bg: 'bg-gradient-to-br from-blue-500/10 to-blue-600/5', border: 'border-blue-500/30', accent: 'text-blue-400' },
      { bg: 'bg-gradient-to-br from-purple-500/10 to-purple-600/5', border: 'border-purple-500/30', accent: 'text-purple-400' }
    ];
    const cardColor = colors[idx % colors.length];
    
    return (
      <div className={`cyber-border ${cardColor.bg} ${cardColor.border} p-6 group relative overflow-hidden hover:scale-[1.02] transition-all duration-300`}>
        {/* Corner decorations */}
        <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
        
        {/* Content */}
        <div className="relative z-10">
          {/* Card content here */}
        </div>
        
        {/* Bottom hover line */}
        <div className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
      </div>
    );
  })}
</div>
```

## 🎪 Decorative Elements

### 1. **Status Indicators**
```jsx
/* System status */
<div className="flex items-center gap-2">
  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
  <span className="text-xs text-emerald-500 font-mono uppercase">ONLINE</span>
</div>

/* Progress dots */
<div className="flex gap-1">
  {[1,2,3].map(i => <div key={i} className="w-1 h-1 bg-emerald-400 opacity-60" />)}
</div>
```

### 2. **Border Decorations**  
```jsx
/* Corner brackets */
<div className="absolute top-2 right-2 w-3 h-3 border-t-2 border-r-2 border-emerald-400 opacity-60"></div>
<div className="absolute bottom-2 left-2 w-3 h-3 border-b-2 border-l-2 border-cyan-400 opacity-60"></div>

/* Gradient lines */
<div className="w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
```

### 3. **Background Effects**
```jsx
/* Grid pattern */
<div className="pointer-events-none absolute inset-0 opacity-[0.03] dark:opacity-[0.07]"
     style={{
       backgroundImage: "repeating-linear-gradient(0deg, transparent, transparent 23px, #22c55e44 24px), repeating-linear-gradient(90deg, transparent, transparent 23px, #22c55e44 24px)"
     }}
/>

/* Scan lines */
<div className="pointer-events-none absolute inset-0 opacity-[0.02] dark:opacity-[0.05]"
     style={{
       backgroundImage: "repeating-linear-gradient(0deg, transparent, transparent 1px, #00ff0022 2px)"
     }}
/>
```

## 📏 Implementation Rules

### ✅ ALWAYS DO
1. **Use cyber-border for all containers** - Never plain rectangles
2. **Apply consistent color rotation** - Emerald → Blue → Purple → repeat
3. **Add hover effects** - Scale, glow, color transitions
4. **Include status indicators** - Pulsing dots, system status
5. **Use monospace fonts** for technical elements
6. **Lowercase everything** except status labels
7. **Add corner decorations** on interactive elements
8. **Smooth transitions** (300ms duration)
9. **Proper text contrast** using theme-aware colors

### ❌ NEVER DO  
1. **Plain rectangles** - Always use angled corners
2. **Boring gray cards** - Use colorful gradients
3. **Static elements** - Add animations and hover effects
4. **Mixed case randomly** - Follow typography hierarchy
5. **Hard-coded colors** - Use theme-aware CSS variables
6. **Instant changes** - Always transition smoothly

## 🔧 Usage Example

When creating a new page/component, follow this pattern:

```jsx
import React from 'react';
import AppLayout from '@/Layouts/AppLayout';

export default function MyPage({ data }) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="space-y-8">
        {/* Header with decorations */}
        <div className="text-center space-y-4 relative">
          <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
          
          <div className="flex items-center justify-center gap-3">
            <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
            <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">page label</span>
            <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
          </div>
          
          <h1 className="text-2xl font-medium lowercase glow-text text-foreground">page title</h1>
        </div>

        {/* Content sections with consistent styling */}
        {/* ... */}
      </div>
    </AppLayout>
  );
}
```

This design system ensures every page maintains the same gaming aesthetic, color consistency, and interactive feel across the entire application.