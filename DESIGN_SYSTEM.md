# Minimal Design System

**Version:** 3.0
**Framework:** React + Inertia + Tailwind CSS
**Aesthetic:** Clean, Minimal, Professional

## 🎨 Design Philosophy

- **Minimal Aesthetic**: Clean, Ghost-inspired design with subtle elements
- **High Readability**: Excellent contrast ratios, clear typography hierarchy
- **Subtle Interactions**: Gentle hover effects, smooth transitions
- **Consistent Colors**: Monochromatic palette with theme-aware colors
- **Professional Look**: Modern, clean appearance suitable for any audience

## 🧩 Core Components

### 1. **Clean Cards** (`clean-card`)
```css
/* Subtle borders with rounded corners */
border: 1px solid hsl(var(--border));
border-radius: var(--radius);
background: hsl(var(--card));
```
**Usage:** All cards, containers
**Rule:** Always use subtle borders and rounded corners

### 2. **Clean Buttons** (`clean-button`)
```jsx
<button className="clean-button px-4 py-2">
  Button Text
</button>

{/* Primary button */}
<button className="clean-button primary px-4 py-2">
  Primary Action
</button>
```
**Features:**
- Rounded corners (`border-radius: var(--radius)`)
- Subtle hover effects
- Theme-aware colors
- Clean typography

### 3. **Color Palette**

#### Theme Colors
- **Background**: `hsl(var(--background))`
- **Foreground**: `hsl(var(--foreground))`
- **Card**: `hsl(var(--card))`
- **Muted**: `hsl(var(--muted-foreground))`
- **Border**: `hsl(var(--border))`

#### Usage Examples
```jsx
className="bg-background text-foreground"
className="bg-card text-card-foreground"
className="text-muted-foreground" // for secondary text
className="border-border" // for borders
```

### 4. **Typography Hierarchy**

```jsx
/* Page Title */
<h1 className="text-2xl font-semibold text-foreground">

/* Section Title */
<h2 className="text-lg font-medium text-foreground">

/* Card Title */
<h3 className="text-base font-medium text-foreground">

/* Body Text */
<p className="text-muted-foreground">

/* Small Text/Captions */
<span className="text-sm text-muted-foreground">
```

**Rules:**
- Use natural case (not forced lowercase)
- Consistent font weights (medium/semibold for headings)
- Clear hierarchy with proper sizing

### 5. **Interactive Elements**

#### Hover Effects
```jsx
/* Subtle shadow for cards */
hover:shadow-sm transition-all duration-200

/* Background change for buttons */
hover:bg-accent

/* Border highlight */
hover:border-border/80
```

#### States
```jsx
/* Loading */
<div className="animate-pulse bg-muted rounded h-4 w-full"></div>

/* Active/Selected */
<div className="bg-accent border-border">
```

## 🎯 Layout Patterns

### 1. **Page Structure**
```jsx
<div className="space-y-6">
  {/* Welcome Header */}
  <div className="text-center space-y-2 mb-8">
    <h1 className="text-2xl font-semibold text-foreground">Page Title</h1>
    <p className="text-muted-foreground">Brief description of the page</p>
  </div>

  {/* Content sections with consistent spacing */}
  <div className="space-y-6">
    {/* Content goes here */}
  </div>
</div>
```

### 2. **Section Headers**
```jsx
<div className="border-b border-border pb-3 mb-6">
  <h2 className="text-lg font-medium text-foreground">Section Title</h2>
  <p className="text-sm text-muted-foreground">Optional description</p>
</div>
```

### 3. **Card Grid**
```jsx
<div className="grid md:grid-cols-3 gap-4">
  {items.map((item, idx) => (
    <div key={idx} className="clean-card p-6 hover:shadow-sm transition-all duration-200">
      <h3 className="text-base font-medium text-foreground mb-2">
        {item.title}
      </h3>
      <p className="text-muted-foreground text-sm">
        {item.description}
      </p>
    </div>
  ))}
</div>
```

## 🎪 UI Elements

### 1. **Status Indicators**
```jsx
/* Simple status */
<div className="flex items-center gap-2">
  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
  <span className="text-sm text-muted-foreground">Online</span>
</div>

/* Badge */
<span className="inline-flex items-center px-2 py-1 bg-accent text-accent-foreground text-xs rounded">
  Status
</span>
```

### 2. **Separators**
```jsx
/* Horizontal separator */
<div className="border-b border-border"></div>

/* With content */
<div className="relative">
  <div className="absolute inset-0 flex items-center">
    <div className="w-full border-t border-border"></div>
  </div>
  <div className="relative flex justify-center text-sm">
    <span className="bg-background px-2 text-muted-foreground">Or</span>
  </div>
</div>
```

### 3. **Form Elements**
```jsx
/* Input */
<input className="w-full px-3 py-2 border border-border rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />

/* Select */
<select className="w-full px-3 py-2 border border-border rounded bg-background text-foreground">
  <option>Option 1</option>
</select>
```

## 📏 Implementation Rules

### ✅ ALWAYS DO
1. **Use clean-card for containers** - Subtle borders and rounded corners
2. **Apply consistent spacing** - Use Tailwind space utilities
3. **Use subtle hover effects** - `hover:shadow-sm`, `hover:bg-accent`
4. **Follow typography hierarchy** - Proper heading levels and sizes
5. **Use theme-aware colors** - CSS variables only
6. **Maintain clean layouts** - Proper spacing and alignment
7. **Keep transitions subtle** - 200ms duration max
8. **Ensure good contrast** - Readable text at all times

### ❌ NEVER DO
1. **Gaming/cyber aesthetics** - No neon colors or angular borders
2. **Complex gradients** - Keep backgrounds simple
3. **Excessive animations** - Only subtle hover effects
4. **Hard-coded colors** - Always use CSS variables
5. **Inconsistent spacing** - Use Tailwind space utilities
6. **Overly decorative elements** - Keep it clean and minimal

## 🔧 Usage Example

When creating a new page/component, follow this pattern:

```jsx
import React from 'react';
import AppLayout from '@/Layouts/AppLayout';

export default function MyPage({ data }) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="space-y-6">
        {/* Clean header */}
        <div className="text-center space-y-2 mb-8">
          <h1 className="text-2xl font-semibold text-foreground">Page Title</h1>
          <p className="text-muted-foreground">Description of this page</p>
        </div>

        {/* Content section */}
        <div className="border-b border-border pb-3 mb-6">
          <h2 className="text-lg font-medium text-foreground">Section</h2>
        </div>

        {/* Cards grid */}
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.map((item, idx) => (
            <div key={idx} className="clean-card p-6 hover:shadow-sm transition-all duration-200">
              <h3 className="text-base font-medium text-foreground mb-2">
                {item.title}
              </h3>
              <p className="text-muted-foreground text-sm">
                {item.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
```

This design system ensures every page maintains a clean, professional, and consistent appearance across the entire application, inspired by modern minimal design like Ghost's documentation.