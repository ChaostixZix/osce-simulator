# SEO Implementation Guide for Vibe Kanban

This guide explains how to work with the SEO features implemented in Vibe Kanban.

## Features Implemented

### 1. Comprehensive Meta Tags
- **Core Meta**: Title, description, keywords, author, canonical URL
- **Open Graph**: For social media sharing (Facebook, LinkedIn)
- **Twitter Cards**: Optimized for Twitter sharing
- **Structured Data (JSON-LD)**: Schema.org markup for better search understanding
- **Favicons**: Complete set with multiple sizes and formats

### 2. Dynamic SEO Support
- SEO Service (`app/Services/SeoService.php`) for generating metadata
- Middleware integration for sharing SEO data with all pages
- Support for page-specific meta tags via Inertia.js Head component

### 3. Sitemap Generation
- Dynamic sitemap generation at `/sitemap.xml`
- Sitemap index support for large sites
- Automatic inclusion of published OSCE cases

### 4. Robots.txt
- Dynamic robots.txt generation at `/robots.txt`
- Proper blocking of admin and sensitive areas
- Crawl directives for better indexing

## Usage

### 1. Adding Page-Specific Meta Tags

In any React page component:

```jsx
import { Head } from '@inertiajs/react';

export default function MyPage() {
    return (
        <>
            <Head>
                <title>My Page Title</title>
                <meta name="description" content="Custom description for this page" />
                <meta property="og:title" content="Custom OG Title" />
                <meta property="og:description" content="Custom OG description" />
                <meta property="og:image" content="/page-specific-image.png" />
                <meta name="twitter:card" content="summary_large_image" />
                
                {/* Structured Data for specific content */}
                <script type="application/ld+json">
                    {JSON.stringify({
                        "@context": "https://schema.org",
                        "@type": "Course",
                        "name": "Page Title",
                        "description": "Page description",
                        "provider": {
                            "@type": "Organization",
                            "name": "Vibe Kanban",
                            "url": config('app.url')
                        }
                    })}
                </script>
            </Head>
            
            {/* Page content */}
        </>
    );
}
```

### 2. Using SEO Service

```php
use App\Services\SeoService;

// Generate meta tags for a page
$meta = SeoService::generateMetaTags([
    'title' => 'Custom Title',
    'description' => 'Custom description',
    'keywords' => 'custom, keywords',
    'type' => 'article'
]);

// Generate structured data
$structuredData = SeoService::generateStructuredData('Article', [
    'headline' => 'Article Title',
    'datePublished' => now()->toIso8601String(),
    'articleSection' => 'Medical Education'
]);
```

### 3. Controller Example

```php
public function show($id)
{
    $case = OsceCase::findOrFail($id);
    
    $seo = SeoService::generateMetaTags([
        'title' => $case->title,
        'description' => $case->description,
        'keywords' => $case->tags,
        'type' => 'article',
        'image' => $case->image_url
    ]);
    
    $structuredData = SeoService::generateStructuredData('Course', [
        'name' => $case->title,
        'description' => $case->description,
        'courseCode' => $case->code,
        'hasCourseInstance' => [
            '@type' => 'CourseInstance',
            'courseMode' => 'online',
            'instructor' => [
                '@type' => 'Person',
                'name' => 'AI Assistant'
            ]
        ]
    ]);
    
    return Inertia::render('Osce/CaseDetail', [
        'case' => $case,
        'seo' => [
            'meta' => $seo,
            'structuredData' => $structuredData
        ]
    ]);
}
```

## SEO Routes

- `/robots.txt` - Dynamic robots.txt
- `/sitemap.xml` - Main sitemap
- `/sitemap-cases.xml` - Cases sitemap
- `/sitemap_index.xml` - Sitemap index

## Environment Variables

Add these to your `.env` file:

```env
APP_NAME=Vibe Kanban
APP_URL=https://your-domain.com

# SEO Configuration
APP_DESCRIPTION="Your application description"
APP_AUTHOR="Bintang Putra"
APP_AUTHOR_URL="https://bintangputra.my.id"
APP_AUTHOR_TWITTER="bintangputra"
```

## Image Requirements

Create these images and place in `/public/`:
- `favicon.svg` - Modern SVG favicon (recommended: 32x32)
- `favicon.ico` - Legacy favicon (recommended: 16x16, 32x32)
- `apple-touch-icon.png` - Apple device icon (180x180)
- `favicon-16x16.png` - 16x16 PNG
- `favicon-32x32.png` - 32x32 PNG
- `android-chrome-192x192.png` - Android icon (192x192)
- `android-chrome-512x512.png` - Android icon (512x512)
- `og-image.png` - Open Graph image (1200x630)
- `twitter-image.png` - Twitter Card image (1200x675)
- `logo.png` - Logo for structured data (400x400)

## Testing

1. Test meta tags with [Meta Tags Checker](https://metatags.io/)
2. Validate structured data with [Google Rich Results Test](https://search.google.com/test/rich-results)
3. Test sitemap validity in Google Search Console
4. Check social media sharing with [Twitter Card Validator](https://cards-dev.twitter.com/validator)

## Notes

- The middleware automatically shares basic SEO data with all pages
- Use the Head component in React pages for page-specific meta
- Structured data can be added both globally and per-page
- Sitemap automatically includes published OSCE cases
- Robots.txt blocks admin and sensitive areas by default