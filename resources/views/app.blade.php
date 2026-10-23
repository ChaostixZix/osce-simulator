<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            try {
                const stored = localStorage.getItem('theme');
                const dark = stored === 'dark' || ((!stored || stored === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch {}
        </script>
        <title inertia>{{ config('app.name', 'OSCE Simulator') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preload" href="/fonts/instrument-sans-400.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="/fonts/instrument-sans.css">
        @routes
        @php
            $broadcasting = [
                'driver' => config('broadcasting.default'),
                'key' => config('broadcasting.connections.reverb.key'),
                'wsHost' => config('broadcasting.connections.reverb.options.host'),
                'wsPort' => config('broadcasting.connections.reverb.options.port', 80),
                'wssPort' => config('broadcasting.connections.reverb.options.port', 443),
                'forceTLS' => config('broadcasting.connections.reverb.options.scheme') === 'https',
            ];
        @endphp
        <script>window.Laravel = { broadcasting: @json($broadcasting) };</script>
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
