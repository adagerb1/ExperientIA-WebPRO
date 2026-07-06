@extends('layouts.site')
@section('title'){{ __('site.soluciones.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.soluciones.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.soluciones.eyebrow')" :titulo="__('site.soluciones.titulo')" :sub="__('site.soluciones.sub')" />

  <section class="section">
    <div class="container sol-list__grid">
      @foreach ($solutions as $i => $s)
        <article class="card card-lum sol-item reveal" style="--reveal-delay:{{ ($i % 2) * 0.08 }}s">
          <header class="sol-item__head">
            <span class="icon-chip"><x-ei-icon :name="$s->icon" /></span>
            <div>
              <p class="sol-item__pilar">{{ tr($s->pilar) }}</p>
              <h2 class="h3">{{ tr($s->titulo) }}</h2>
            </div>
          </header>
          <div>
            <h3 class="sol-item__label">{{ __('site.common.problema_label') }}</h3>
            <p>{{ tr($s->problema) }}</p>
          </div>
          <div>
            <h3 class="sol-item__label">{{ __('site.common.como_label') }}</h3>
            <ul class="sol-item__list">
              @foreach (tr_lines($s->como) as $linea)
                <li><span class="check-chip"><x-ei-icon name="check" :size="13" /></span>{{ $linea }}</li>
              @endforeach
            </ul>
          </div>
          <div class="sol-item__block--cambia">
            <h3 class="sol-item__label sol-item__label--accent">{{ __('site.common.cambia_label') }}</h3>
            <p>{{ tr($s->cambia) }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </section>

  <section class="section metodo">
    <div class="bg-atmos" aria-hidden="true">
      <div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-240px;opacity:.3;"></div>
    </div>
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.soluciones.metodo_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.soluciones.metodo_titulo') }}</h2>
      </div>
      <ol class="metodo__steps">
        @foreach (__('site.soluciones.metodo') as $i => $paso)
          <li class="card reveal" style="--reveal-delay:{{ $i * 0.1 }}s">
            <span class="metodo__num grad-text">{{ $paso['num'] }}</span>
            <h3 class="h3">{{ $paso['titulo'] }}</h3>
            <p>{{ $paso['texto'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.home.cta_titulo')"
    :sub="__('site.home.cta_sub')"
    :primaryHref="lroute('contacto')"
    :primaryLabel="__('site.home.cta_cta1')"
    :secondaryHref="lroute('diagnostico')"
    :secondaryLabel="__('site.common.hacer_diagnostico')"
  />
@endsection
