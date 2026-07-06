@extends('layouts.site')
@section('title'){{ __('site.nosotros.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.nosotros.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.nosotros.eyebrow')" :titulo="__('site.nosotros.titulo')" :sub="__('site.nosotros.sub')" />

  <section class="section valores">
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.nosotros.esencia_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.nosotros.esencia_titulo') }}</h2>
      </div>
      <div class="grid grid-4">
        @foreach (__('site.nosotros.valores') as $i => $v)
          <article class="card reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
            <span class="icon-chip"><x-ei-icon :name="$v['icon']" /></span>
            <h3 class="h3" style="font-size:1.05rem">{{ $v['titulo'] }}</h3>
            <p class="small">{{ $v['texto'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section nos-pers">
    <div class="bg-atmos" aria-hidden="true">
      <div class="halo halo-violet" style="width:460px;height:460px;top:-140px;left:-240px;opacity:.3;"></div>
    </div>
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.nosotros.personalidad_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.nosotros.personalidad_titulo') }}</h2>
      </div>
      <div class="nos-pers__grid">
        <div class="nos-pers__symbol reveal" aria-hidden="true">
          <x-brand-symbol variant="gradient" :size="150" class="anim-float" />
        </div>
        <div class="nos-pers__rasgos">
          @foreach (__('site.nosotros.rasgos') as $i => $r)
            <div class="nos-pers__rasgo reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
              <h3 class="h3 grad-text">{{ $r['titulo'] }}</h3>
              <p>{{ $r['texto'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  <section class="section nos-eco">
    <div class="container">
      <div class="card-lum nos-eco__panel reveal">
        <div class="bg-atmos" aria-hidden="true">
          <div class="bg-skyline"></div>
          <div class="halo halo-cyan" style="width:420px;height:420px;top:-240px;right:-100px;opacity:.35;"></div>
        </div>
        <div class="nos-eco__content">
          <p class="eyebrow">{{ __('site.nosotros.eco_eyebrow') }}</p>
          <h2 class="h2">{{ __('site.nosotros.eco_titulo') }}</h2>
          <p class="lead">{{ __('site.nosotros.eco_texto') }}</p>
          <a href="https://tonnydager.com" class="link-arrow" rel="noopener" target="_blank">
            {{ __('site.nosotros.eco_cta') }}
            <x-ei-icon name="arrow" :size="16" />
          </a>
        </div>
      </div>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.nosotros.cta_titulo')"
    :sub="__('site.nosotros.cta_sub')"
    :primaryHref="lroute('agenda')"
    :primaryLabel="__('site.nosotros.cta_cta')"
  />
@endsection
