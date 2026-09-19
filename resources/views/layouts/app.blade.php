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
<body class="antialiased text-slate-900">
    @include('partials.nav')
    <main>@yield('content')</main>
    @include('partials.footer')
</body>
</html>
