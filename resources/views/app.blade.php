<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

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
