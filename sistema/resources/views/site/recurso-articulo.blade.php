@extends('layouts.site')
@section('title'){{ tr($resource->titulo) }} · ExperientIA @endsection
@section('description'){{ tr($resource->extracto) }}@endsection

@section('content')
  <x-page-hero :eyebrow="tr($resource->tipo_label)" :titulo="tr($resource->titulo)" :sub="tr($resource->extracto)" />

  <section class="section">
    <div class="container articulo">
      <div class="articulo__cuerpo reveal">
        {!! tr($resource->cuerpo) !!}
      </div>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.home.cta_titulo')"
    :sub="__('site.home.cta_sub')"
    :primaryHref="lroute('contacto')"
    :primaryLabel="__('site.home.cta_cta1')"
    :secondaryHref="lroute('recursos')"
    :secondaryLabel="__('site.nav.recursos')"
  />
@endsection
