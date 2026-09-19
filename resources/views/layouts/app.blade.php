<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))</title>
    <meta name="description" content="@yield('meta_description', \App\Models\Setting::get('default_meta_description'))">
    @include('components.seo.hreflang')
    @include('components.seo.canonical')
    {{-- `meta` is the escape hatch for page-specific head tags (Open Graph,
         robots) that do not warrant their own slot; `schema` carries inline
         JSON-LD, with `@stack` for pages that build it from a partial. --}}
    @yield('meta')
    @yield('schema')
    @stack('schema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink antialiased">
    {{-- Skip link: the header is on every page, so keyboard users need a way
         past it. Visible only on focus. --}}
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-control focus:bg-accent focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-accent-ink">
        {{ __('ui.nav.skip') }}
    </a>

    @include('partials.nav')

    <main id="main">@yield('content')</main>

    @include('partials.footer')
</body>
</html>
