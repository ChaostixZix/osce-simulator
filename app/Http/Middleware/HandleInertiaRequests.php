<?php

namespace App\Http\Middleware;

use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Route;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        $seo = SeoService::generateMetaTags([
            'url' => $request->fullUrl(),
        ]);

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'seo' => [
                'meta' => $seo,
                'structuredData' => SeoService::generateStructuredData('EducationalOrganization'),
                'breadcrumbs' => $this->generateBreadcrumbs(),
            ],
            'location' => [
                'timezone' => config('app.timezone'),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ]);
    }

    /**
     * Generate breadcrumbs for current route
     *
     * @return array
     */
    protected function generateBreadcrumbs(): array
    {
        $route = Route::current();
        $uri = $route->uri();
        
        $breadcrumbs = [
            [
                'name' => 'Home',
                'url' => route('dashboard'),
            ],
        ];

        // Add route-specific breadcrumbs
        if ($uri !== 'dashboard') {
            $segments = explode('/', $uri);
            $currentUrl = '';
            
            foreach ($segments as $segment) {
                if ($segment !== '' && !str_contains($segment, '{')) {
                    $currentUrl .= '/' . $segment;
                    $breadcrumbs[] = [
                        'name' => ucfirst(str_replace('-', ' ', $segment)),
                        'url' => $currentUrl,
                    ];
                }
            }
        }

        return $breadcrumbs;
    }
}