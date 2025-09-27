<?php

namespace App\Services;

use Illuminate\Support\Str;

class SeoService
{
    /**
     * Generate SEO meta tags for a page
     *
     * @param array $options
     * @return array
     */
    public static function generateMetaTags(array $options = []): array
    {
        $defaults = [
            'title' => config('app.name'),
            'description' => config('app.description'),
            'keywords' => 'OSCE, medical education, clinical skills, medical training, patient simulation, AI in healthcare, medical students',
            'author' => config('app.author'),
            'image' => config('app.url') . '/og-image.png',
            'type' => 'website',
            'url' => request()->url(),
            'canonical' => request()->url(),
            'robots' => 'index, follow',
            'published_time' => null,
            'modified_time' => null,
            'section' => null,
            'tags' => null,
        ];

        $options = array_merge($defaults, $options);

        // Ensure title is not too long
        $options['title'] = Str::limit($options['title'], 60, '');
        
        // Ensure description is not too long
        $options['description'] = Str::limit($options['description'], 160, '');

        return $options;
    }

    /**
     * Generate structured data (JSON-LD) for different content types
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function generateStructuredData(string $type, array $data = []): array
    {
        $baseData = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => config('app.name'),
            'url' => config('app.url'),
            'description' => config('app.description'),
            'logo' => config('app.url') . '/logo.png',
            'image' => config('app.url') . '/og-image.png',
            'author' => [
                '@type' => 'Person',
                'name' => config('app.author'),
                'url' => config('app.author_url'),
            ],
        ];

        switch ($type) {
            case 'EducationalOrganization':
                $baseData = array_merge($baseData, [
                    'knowsAbout' => ["OSCE", "Medical Education", "Clinical Skills", "Healthcare Simulation", "AI in Medicine"],
                    'educationalLevel' => 'Higher Education',
                    'areaServed' => 'Worldwide',
                    'sameAs' => [config('app.author_url')],
                ]);
                break;
                
            case 'Course':
                $baseData = array_merge($baseData, [
                    'provider' => [
                        '@type' => 'EducationalOrganization',
                        'name' => config('app.name'),
                        'url' => config('app.url'),
                    ],
                    'courseCode' => $data['courseCode'] ?? null,
                    'hasCourseInstance' => $data['hasCourseInstance'] ?? null,
                    'learningResourceType' => 'course',
                    'occupationalCredentialAwarded' => $data['occupationalCredentialAwarded'] ?? null,
                ]);
                break;
                
            case 'Article':
                $baseData = array_merge($baseData, [
                    'headline' => $data['headline'] ?? null,
                    'datePublished' => $data['datePublished'] ?? now()->toIso8601String(),
                    'dateModified' => $data['dateModified'] ?? now()->toIso8601String(),
                    'author' => [
                        '@type' => 'Person',
                        'name' => config('app.author'),
                        'url' => config('app.author_url'),
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config('app.name'),
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => config('app.url') . '/logo.png',
                        ],
                    ],
                    'articleSection' => $data['articleSection'] ?? 'Medical Education',
                    'keywords' => $data['keywords'] ?? 'OSCE, medical education',
                ]);
                break;
        }

        return array_merge($baseData, $data);
    }

    /**
     * Generate breadcrumb structured data
     *
     * @param array $breadcrumbs
     * @return array
     */
    public static function generateBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        $position = 1;
        
        foreach ($breadcrumbs as $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate sitemap URL set
     *
     * @param array $urls
     * @return string
     */
    public static function generateSitemap(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($url['loc']) . '</loc>';
            
            if (isset($url['lastmod'])) {
                $xml .= '<lastmod>' . date('Y-m-d', strtotime($url['lastmod'])) . '</lastmod>';
            }
            
            if (isset($url['changefreq'])) {
                $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
            }
            
            if (isset($url['priority'])) {
                $xml .= '<priority>' . $url['priority'] . '</priority>';
            }
            
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }

    /**
     * Get social media image URL based on platform
     *
     * @param string $platform
     * @return string
     */
    public static function getSocialImage(string $platform = 'facebook'): string
    {
        switch ($platform) {
            case 'twitter':
                return config('app.url') . '/twitter-image.png';
            case 'facebook':
            case 'linkedin':
            default:
                return config('app.url') . '/og-image.png';
        }
    }
}