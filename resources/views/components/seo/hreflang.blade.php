@php
    $alternates = \App\Support\LocaleUrls::alternates(request());
@endphp

@foreach ($alternates as $altLocale => $url)
    <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $url }}">
@endforeach

@if ($alternates !== [])
    <link rel="alternate" hreflang="x-default" href="{{ \App\Support\LocaleUrls::switchTo(request(), 'id') }}">
@endif
