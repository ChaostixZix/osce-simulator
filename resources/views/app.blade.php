<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <!-- Core Meta Tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ config('app.description', 'Vibe Kanban is a comprehensive OSCE training platform for medical students. Practice, track progress, and master clinical skills with AI-powered patient simulations and real-time feedback.') }}">
        <meta name="keywords" content="OSCE, medical education, clinical skills, medical training, patient simulation, AI in healthcare, medical students">
        <meta name="author" content="{{ config('app.author', 'Bintang Putra') }}">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#1e293b">
        <link rel="canonical" href="{{ request()->url() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        
        <!-- Additional Favicon Sizes for Better Device Support -->
        <link rel="icon" href="/favicon-32x32.png" sizes="32x32" type="image/png">
        <link rel="icon" href="/favicon-16x16.png" sizes="16x16" type="image/png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#1e293b">
        <meta name="msapplication-TileColor" content="#1e293b">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Open Graph Tags -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name', 'Vibe Kanban') }}">
        <meta property="og:description" content="{{ config('app.description', 'Vibe Kanban is a comprehensive OSCE training platform for medical students. Practice, track progress, and master clinical skills with AI-powered patient simulations and real-time feedback.') }}">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Vibe Kanban') }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
        <meta property="og:image" content="{{ config('app.url') }}/logo.svg">
        <meta property="og:image:width" content="200">
        <meta property="og:image:height" content="60">
        
        <!-- Twitter Card Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ config('app.name', 'Vibe Kanban') }}">
        <meta name="twitter:description" content="{{ config('app.description', 'Vibe Kanban is a comprehensive OSCE training platform for medical students. Practice, track progress, and master clinical skills with AI-powered patient simulations and real-time feedback.') }}">
        <meta name="twitter:image" content="{{ config('app.url') }}/logo.svg">
        <meta name="twitter:creator" content="@{{ config('app.author_twitter', 'bintangputra') }}">
        
        <!-- Structured Data (JSON-LD) -->
        <script type="application/ld+json">
        {!! json_encode([
            "@@context" => "https://schema.org",
            "@@type" => "EducationalOrganization",
            "name" => config('app.name', 'Vibe Kanban'),
            "url" => config('app.url'),
            "description" => config('app.description', 'Vibe Kanban is a comprehensive OSCE training platform for medical students. Practice, track progress, and master clinical skills with AI-powered patient simulations and real-time feedback.'),
            "logo" => config('app.url') . "/logo.svg",
            "image" => config('app.url') . "/logo.svg",
            "author" => [
                "@@type" => "Person",
                "name" => config('app.author', 'Bintang Putra'),
                "url" => config('app.author_url', 'https://bintangputra.my.id')
            ],
            "sameAs" => [config('app.author_url', 'https://bintangputra.my.id')],
            "knowsAbout" => ["OSCE", "Medical Education", "Clinical Skills", "Healthcare Simulation", "AI in Medicine"],
            "educationalLevel" => "Higher Education",
            "areaServed" => "Worldwide"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>

        @routes
        
        {{-- Broadcasting configuration for Laravel Echo --}}
        <script>
            window.Laravel = window.Laravel || {};
            window.Laravel.broadcasting = {
                driver: '{{ config("broadcasting.default") }}'
                @if(config('broadcasting.default') === 'reverb')
                ,key: '{{ config("broadcasting.connections.reverb.key") }}'
                ,wsHost: '{{ config("broadcasting.connections.reverb.host") }}'
                ,wsPort: {{ config("broadcasting.connections.reverb.port") }}
                ,wssPort: {{ config("broadcasting.connections.reverb.port") }}
                ,forceTLS: {{ config("broadcasting.connections.reverb.scheme") === 'https' ? 'true' : 'false' }}
                @endif
                @if(config('broadcasting.default') === 'pusher')
                ,key: '{{ config("broadcasting.connections.pusher.key") }}'
                ,cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}'
                @endif
            };
        </script>
        
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
