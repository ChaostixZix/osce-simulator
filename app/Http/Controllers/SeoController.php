<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SeoService;

class SeoController extends Controller
{
    /**
     * Serve dynamic robots.txt
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function robots(Request $request)
    {
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /_ignition/\n";
        $robots .= "Disallow: /storage/\n";
        $robots .= "Disallow: /vendor/\n";
        $robots .= "Disallow: /nova-api/\n";
        $robots .= "Disallow: /nova-vendor/\n";
        $robots .= "Disallow: /horizon/\n";
        $robots .= "Disallow: /telescope/\n";
        $robots .= "Disallow: /login\n";
        $robots .= "Disallow: /register\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /*?*\n"; // Block all URLs with parameters
        $robots .= "\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /osce/\n";
        $robots .= "Allow: /cases/\n";
        $robots .= "Allow: /dashboard\n";
        $robots .= "\n";
        $robots .= "Sitemap: " . config('app.url') . "/sitemap.xml\n";
        $robots .= "Crawl-delay: 1\n";
        
        return response($robots, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Generate and serve sitemap.xml
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sitemap(Request $request)
    {
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('dashboard'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('osce.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
        ];

        // Add OSCE case URLs
        if (class_exists(\App\Models\OsceCase::class)) {
            $cases = \App\Models\OsceCase::where('published', true)->get();
            foreach ($cases as $case) {
                $urls[] = [
                    'loc' => route('osce.index') . '/case/' . $case->id,
                    'lastmod' => $case->updated_at->toDateString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }
        }

        $sitemap = SeoService::generateSitemap($urls);

        return response($sitemap, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate and serve cases sitemap
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sitemapCases(Request $request)
    {
        $urls = [];

        // Add OSCE case URLs
        if (class_exists(\App\Models\OsceCase::class)) {
            $cases = \App\Models\OsceCase::where('published', true)->get();
            foreach ($cases as $case) {
                $urls[] = [
                    'loc' => route('osce.index') . '/case/' . $case->id,
                    'lastmod' => $case->updated_at->toDateString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }
        }

        $sitemap = SeoService::generateSitemap($urls);

        return response($sitemap, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate and serve sitemap index for large sites
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sitemapIndex(Request $request)
    {
        $sitemapUrls = [
            ['loc' => route('sitemap.main'), 'lastmod' => now()->toDateString()],
        ];

        // Add additional sitemaps for different content types
        if (class_exists(\App\Models\OsceCase::class)) {
            $sitemapUrls[] = [
                'loc' => route('sitemap.cases'),
                'lastmod' => \App\Models\OsceCase::where('published', true)->max('updated_at')->toDateString() ?? now()->toDateString(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($sitemapUrls as $sitemap) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . htmlspecialchars($sitemap['loc']) . '</loc>';
            $xml .= '<lastmod>' . $sitemap['lastmod'] . '</lastmod>';
            $xml .= '</sitemap>';
        }
        
        $xml .= '</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}