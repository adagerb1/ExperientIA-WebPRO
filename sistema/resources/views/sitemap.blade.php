<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $u)
  <url>
    <loc>{{ $u['loc'] }}</loc>
@foreach ($u['alternates'] as $l => $href)
    <xhtml:link rel="alternate" hreflang="{{ $l }}" href="{{ $href }}" />
@endforeach
@if (!empty($u['lastmod']))
    <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
  </url>
@endforeach
</urlset>
