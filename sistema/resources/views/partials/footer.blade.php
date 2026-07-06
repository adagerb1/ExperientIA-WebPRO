@php
$social = [
  ['name' => 'Instagram', 'icon' => 'instagram', 'url' => 'https://www.instagram.com/experientia.sas/'],
  ['name' => 'LinkedIn', 'icon' => 'linkedin', 'url' => 'https://www.linkedin.com/in/tonny-dager/'],
  ['name' => 'Facebook', 'icon' => 'facebook', 'url' => 'https://www.facebook.com/Experientia.SAS'],
];
@endphp
<footer class="site-footer">
  <div class="container">
    <hr class="divider" />
    <div class="site-footer__grid">
      <div class="site-footer__brand">
        <a href="{{ lroute('home') }}" aria-label="ExperientIA">
          <x-brand-logo variant="gradient" :descriptor="true" :size="46" />
        </a>
        <p class="site-footer__tagline">{{ __('site.footer.tagline') }}</p>
        <div class="site-footer__social" aria-label="{{ __('site.footer.seguir') }}">
          @foreach ($social as $s)
            <a href="{{ $s['url'] }}" rel="noopener" target="_blank" aria-label="{{ $s['name'] }}" title="{{ $s['name'] }}">
              <x-ei-icon :name="$s['icon']" :size="18" />
            </a>
          @endforeach
        </div>
      </div>
      <nav aria-label="{{ __('site.footer.col_soluciones') }}">
        <h2 class="site-footer__title">{{ __('site.footer.col_soluciones') }}</h2>
        <ul>
          @foreach (['soluciones', 'tablero', 'productos', 'diagnostico'] as $k)
            <li><a href="{{ lroute($k) }}">{{ __("site.nav.{$k}") !== "site.nav.{$k}" ? __("site.nav.{$k}") : __('site.diagnostico.eyebrow') }}</a></li>
          @endforeach
        </ul>
      </nav>
      <nav aria-label="{{ __('site.footer.col_compania') }}">
        <h2 class="site-footer__title">{{ __('site.footer.col_compania') }}</h2>
        <ul>
          @foreach (['casos', 'recursos', 'nosotros', 'faq', 'contacto'] as $k)
            <li><a href="{{ lroute($k) }}">{{ __("site.nav.{$k}") }}</a></li>
          @endforeach
        </ul>
      </nav>
      <div>
        <h2 class="site-footer__title">{{ __('site.footer.col_contacto') }}</h2>
        <ul>
          <li><a href="mailto:{{ config('experientia.contact_email') }}">{{ config('experientia.contact_email') }}</a></li>
          <li><a href="{{ lroute('agenda') }}">{{ __('site.common.agendar_sesion') }}</a></li>
          <li><span class="site-footer__muted">{{ __('site.footer.contacto_linea') }}</span></li>
        </ul>
        <h2 class="site-footer__title site-footer__title--langs">{{ __('site.footer.idiomas') }}</h2>
        <div class="lang-switch">
          @foreach (['es', 'en', 'pt'] as $l)
            <a href="{{ locale_switch_url($l) }}" @class(['is-active' => app()->getLocale() === $l]) lang="{{ $l }}">{{ strtoupper($l) }}</a>
          @endforeach
        </div>
      </div>
    </div>
    <div class="site-footer__legal">
      <p>© {{ date('Y') }} ExperientIA. {{ __('site.footer.legal') }}</p>
      <p>{{ __('site.footer.ecosistema') }} <a href="https://tonnydager.com" rel="noopener" target="_blank">Tonny Dager</a></p>
    </div>
  </div>
</footer>
