@php
$navItems = ['soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq'];
$routeName = optional(request()->route())->getName();
@endphp
<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a href="{{ lroute('home') }}" class="site-header__brand" aria-label="{{ __('site.nav.home_label') }}">
      <x-brand-logo variant="gradient" :descriptor="false" :size="36" />
    </a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        @foreach ($navItems as $item)
          <li>
            <a href="{{ lroute($item) }}" @if (str_ends_with($routeName ?? '', ".{$item}")) aria-current="page" @endif>
              {{ __("site.nav.{$item}") }}
            </a>
          </li>
        @endforeach
      </ul>
      <div class="lang-switch" aria-label="{{ __('site.footer.idiomas') }}">
        @foreach (['es', 'en', 'pt'] as $l)
          <a href="{{ locale_switch_url($l) }}" @class(['is-active' => app()->getLocale() === $l]) lang="{{ $l }}">{{ strtoupper($l) }}</a>
        @endforeach
      </div>
      <a href="{{ lroute('contacto') }}" class="btn btn-primary site-header__cta">{{ __('site.nav.cta') }}</a>
    </nav>
    <button class="site-header__toggle" type="button" aria-expanded="false" aria-label="{{ __('site.nav.menu_label') }}" data-nav-toggle>
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
