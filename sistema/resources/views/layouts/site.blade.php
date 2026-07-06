@php
$locale = app()->getLocale();
$pageTitle = trim($__env->yieldContent('title')) ?: __('site.meta.title_base');
$pageDesc = trim($__env->yieldContent('description')) ?: __('site.meta.description');
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $pageDesc }}" />
  <meta name="theme-color" content="#051126" />
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
  <link rel="canonical" href="{{ url()->current() }}" />
  @foreach (['es', 'en', 'pt'] as $l)
    <link rel="alternate" hreflang="{{ $l }}" href="{{ locale_switch_url($l) }}" />
  @endforeach
  <link rel="alternate" hreflang="x-default" href="{{ locale_switch_url('es') }}" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="ExperientIA" />
  <meta property="og:title" content="{{ $pageTitle }}" />
  <meta property="og:description" content="{{ $pageDesc }}" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:locale" content="{{ ['es' => 'es_ES', 'en' => 'en_US', 'pt' => 'pt_BR'][$locale] }}" />
  <script type="application/ld+json">{!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => 'ExperientIA',
      'slogan' => 'Automatización · Growth · IA',
      'description' => __('site.meta.description'),
      'url' => url('/'),
      'email' => config('experientia.contact_email'),
      'logo' => asset('favicon.svg'),
      'sameAs' => [
          'https://www.instagram.com/experientia.sas/',
          'https://www.linkedin.com/in/tonny-dager/',
          'https://www.facebook.com/Experientia.SAS',
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
  @yield('schema')
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
  @include('partials.header')
  <main id="main">
    @yield('content')
  </main>
  @include('partials.footer')
</body>
</html>
