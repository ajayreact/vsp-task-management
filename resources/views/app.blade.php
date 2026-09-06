<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $sharePreview = \App\Modules\TaskManagement\Support\ShareLinkPreviewMeta::fromInertiaPage($page ?? null);
        @endphp

        <title inertia>{{ $sharePreview['title'] ?? config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="apple-touch-icon" href="/favicon.png">

        {{--
            Open Graph / Twitter cards for public share links.
            Must be server-rendered: WhatsApp/Facebook crawlers do not run React.
            Uses composed V-logo preview — never the full VSP TASK MANAGEMENT wordmark.
        --}}
        @if ($sharePreview)
            <meta property="og:type" content="{{ $sharePreview['type'] }}">
            <meta property="og:site_name" content="{{ config('app.name', 'VSP CRM') }}">
            <meta property="og:title" content="{{ $sharePreview['title'] }}">
            <meta property="og:description" content="{{ $sharePreview['description'] }}">
            <meta property="og:url" content="{{ $sharePreview['url'] }}">
            <meta property="og:image" content="{{ $sharePreview['image'] }}">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:image:type" content="image/png">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $sharePreview['title'] }}">
            <meta name="twitter:description" content="{{ $sharePreview['description'] }}">
            <meta name="twitter:image" content="{{ $sharePreview['image'] }}">
            <link rel="canonical" href="{{ $sharePreview['url'] }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        {{-- Force Light mode before React boots (clears any prior dark preference). --}}
        <script>
            try {
                localStorage.setItem('appearance', 'light');
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
            } catch (e) {}
        </script>

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
