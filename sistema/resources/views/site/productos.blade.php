@extends('layouts.site')
@section('title'){{ __('site.productos.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.productos.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.productos.eyebrow')" :titulo="__('site.productos.titulo')" :sub="__('site.productos.sub')" />

  <section class="section">
    <div class="container prod__grid">
      @foreach ($products as $i => $p)
        <article @class(['card prod__card reveal', 'card-lum prod__card--destacado' => $p->destacado]) style="--reveal-delay:{{ $i * 0.08 }}s">
          <div class="chip-row">
            <span class="icon-chip"><x-ei-icon :name="$p->icon" /></span>
            <span class="chip chip--cyan">{{ tr($p->rol) }}</span>
          </div>
          <h2 class="h3">{{ tr($p->nombre) }}</h2>
          <p>{{ tr($p->texto) }}</p>
          <a href="{{ $p->destacado ? lroute('tablero') : lroute('contacto') }}" class="link-arrow">
            {{ __('site.common.conocer_mas') }}
            <x-ei-icon name="arrow" :size="16" />
          </a>
        </article>
      @endforeach
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.home.cta_titulo')"
    :sub="__('site.home.cta_sub')"
    :primaryHref="lroute('contacto')"
    :primaryLabel="__('site.home.cta_cta1')"
    :secondaryHref="lroute('casos')"
    :secondaryLabel="__('site.nav.casos')"
  />
@endsection
