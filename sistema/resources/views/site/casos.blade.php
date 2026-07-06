@extends('layouts.site')
@section('title'){{ __('site.casos.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.casos.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.casos.eyebrow')" :titulo="__('site.casos.titulo')" :sub="__('site.casos.sub')" />

  <section class="section">
    <div class="container casos__list">
      @foreach ($cases as $i => $c)
        <article class="card card-lum caso reveal" style="--reveal-delay:{{ $i * 0.06 }}s">
          <header>
            <p class="caso__sector">{{ tr($c->sector) }}</p>
            <h2 class="h2 caso__titulo">{{ tr($c->titulo) }}</h2>
          </header>
          <div class="caso__body">
            <div>
              <h3 class="caso__label">{{ __('site.common.contexto') }}</h3>
              <p>{{ tr($c->contexto) }}</p>
            </div>
            <div>
              <h3 class="caso__label">{{ __('site.common.intervencion') }}</h3>
              <p>{{ tr($c->intervencion) }}</p>
            </div>
            <div class="caso__col--res">
              <h3 class="caso__label caso__label--accent">{{ __('site.common.resultados') }}</h3>
              <ul class="caso__resultados">
                @foreach ($c->resultados ?? [] as $r)
                  <li>
                    <span class="caso__valor grad-text">{{ $r['valor'] }}</span>
                    <span class="caso__res-label">{{ tr($r['label']) }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </article>
      @endforeach
      <p class="small casos__nota reveal">{{ __('site.casos.nota') }}</p>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.home.cta_titulo')"
    :sub="__('site.home.cta_sub')"
    :primaryHref="lroute('contacto')"
    :primaryLabel="__('site.home.cta_cta1')"
    :secondaryHref="lroute('soluciones')"
    :secondaryLabel="__('site.common.ver_soluciones')"
  />
@endsection
