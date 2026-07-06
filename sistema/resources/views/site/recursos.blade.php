@extends('layouts.site')
@section('title'){{ __('site.recursos.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.recursos.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.recursos.eyebrow')" :titulo="__('site.recursos.titulo')" :sub="__('site.recursos.sub')" />

  <section class="section">
    <div class="container">
      <div class="grid grid-3">
        @forelse ($resources as $i => $r)
          <article class="card rec__card reveal" style="--reveal-delay:{{ ($i % 3) * 0.08 }}s">
            <div class="chip-row">
              <span class="icon-chip"><x-ei-icon :name="$r->isDownload() ? 'doc' : 'eye'" /></span>
              <span class="chip">{{ tr($r->tipo_label) }}</span>
            </div>
            <h2 class="h3">{{ tr($r->titulo) }}</h2>
            <p class="rec__texto">{{ tr($r->extracto) }}</p>
            <a href="{{ lroute('recurso', ['slug' => $r->slug]) }}" class="link-arrow">
              {{ $r->isDownload() ? __('site.common.descargar') : __('site.common.leer') }}
              <x-ei-icon name="arrow" :size="16" />
            </a>
          </article>
        @empty
          <p class="lead">—</p>
        @endforelse
      </div>
    </div>
  </section>

  <section class="section rec-news" id="newsletter">
    <div class="container">
      <div class="card-lum rec-news__panel reveal">
        <div class="bg-atmos" aria-hidden="true">
          <div class="halo halo-cyan" style="width:400px;height:400px;top:-220px;right:-120px;opacity:.4;"></div>
          <div class="halo halo-violet" style="width:380px;height:380px;bottom:-220px;left:-120px;opacity:.35;"></div>
        </div>
        <div class="rec-news__content">
          <h2 class="h2">{{ __('site.recursos.newsletter_titulo') }}</h2>
          <p class="lead">{{ __('site.recursos.newsletter_sub') }}</p>
          @if (request('news'))
            <p class="form-ok">{{ __('site.recursos.newsletter_gracias') }}</p>
          @else
            <form class="rec-news__form" method="post" action="{{ lroute('newsletter') }}">
              @csrf
              <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off" /></div>
              <label class="visually-hidden" for="news-email">{{ __('site.recursos.newsletter_placeholder') }}</label>
              <input id="news-email" type="email" name="email" placeholder="{{ __('site.recursos.newsletter_placeholder') }}" required />
              <button type="submit" class="btn btn-primary">{{ __('site.recursos.newsletter_cta') }}</button>
            </form>
            @error('email')<p class="error">{{ $message }}</p>@enderror
          @endif
          <p class="small">{{ __('site.recursos.newsletter_nota') }}</p>
        </div>
      </div>
    </div>
  </section>
@endsection
