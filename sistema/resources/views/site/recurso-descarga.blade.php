@extends('layouts.site')
@section('title'){{ tr($resource->titulo) }} · ExperientIA @endsection
@section('description'){{ tr($resource->extracto) }}@endsection

@section('content')
  <x-page-hero :eyebrow="tr($resource->tipo_label)" :titulo="tr($resource->titulo)" :sub="tr($resource->extracto)" />

  <section class="section">
    <div class="container contacto__grid">
      <aside>
        <ul class="beneficios">
          <li class="card reveal"><span class="icon-chip"><x-ei-icon name="doc" /></span><p>{{ tr($resource->extracto) }}</p></li>
          <li class="card reveal" style="--reveal-delay:.08s"><span class="icon-chip"><x-ei-icon name="shield" /></span><p>{{ __('site.form.privacidad') }}</p></li>
        </ul>
      </aside>

      @if (session('download_url') || request('ok'))
        <div class="card card-lum form-panel reveal">
          <h2 class="h3">{{ __('site.recursos.descarga_lista') }}</h2>
          @if (session('download_url'))
            <a href="{{ session('download_url') }}" class="btn btn-primary">{{ __('site.common.descargar') }}</a>
          @endif
          <p class="small">{{ __('site.recursos.gated_sub') }}</p>
        </div>
      @else
        <form class="card card-lum form-panel reveal" method="post" action="{{ lroute('recurso.descargar', ['slug' => $resource->slug]) }}" style="--reveal-delay:.12s">
          @csrf
          <h2 class="h3">{{ __('site.recursos.gated_titulo') }}</h2>
          <p class="small">{{ __('site.recursos.gated_sub') }}</p>
          @include('partials.lead-fields', ['full' => false, 'company' => true])
          <button type="submit" class="btn btn-grad">{{ __('site.common.descargar') }}</button>
          <p class="small">{{ __('site.form.privacidad') }}</p>
        </form>
      @endif
    </div>
  </section>
@endsection
