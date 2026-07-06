@extends('layouts.site')
@section('title'){{ __('site.diagnostico.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.diagnostico.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.diagnostico.eyebrow')" :titulo="__('site.diagnostico.titulo')" :sub="__('site.diagnostico.sub')" />

  <section class="section">
    <div class="container diag">
      @if (request('resultado') && session('diagnostico_resultado'))
        @php $solution = \App\Models\Solution::where('key', session('diagnostico_resultado'))->first(); @endphp
        <div class="card card-lum diag__resultado reveal">
          <p class="eyebrow">{{ __('site.diagnostico.resultado_eyebrow') }}</p>
          <h2 class="h2">{{ __('site.diagnostico.resultado_titulo') }}</h2>
          @if ($solution)
            <div class="chip-row" style="justify-content:flex-start; gap:.9rem">
              <span class="icon-chip"><x-ei-icon :name="$solution->icon" /></span>
              <span class="chip">{{ tr($solution->pilar) }}</span>
            </div>
            <h3 class="h3 grad-text" style="font-size:1.6rem">{{ tr($solution->titulo) }}</h3>
            <p class="lead">{{ tr($solution->cambia) }}</p>
          @endif
          <p>{{ __('site.diagnostico.resultado_sub') }}</p>
          <div class="cta-final__actions" style="justify-content:flex-start">
            <a href="{{ lroute('agenda') }}" class="btn btn-primary">{{ __('site.diagnostico.resultado_cta') }}</a>
            <a href="{{ lroute('soluciones') }}" class="btn btn-ghost">{{ __('site.diagnostico.resultado_cta2') }}</a>
          </div>
        </div>
      @else
        <form method="post" action="{{ lroute('diagnostico.enviar') }}" data-diag>
          @csrf
          <div class="diag__progress" aria-hidden="true"><i></i></div>

          @if ($errors->any())
            <p class="error" style="margin-bottom:1rem">{{ $errors->first() }}</p>
          @endif

          @foreach ($preguntas as $qi => $p)
            <fieldset class="diag__step">
              <legend class="diag__num">{{ __('site.diagnostico.pregunta') }} {{ $qi + 1 }} {{ __('site.diagnostico.de') }} {{ count($preguntas) + 1 }}</legend>
              <h2 class="h3" style="font-size:1.35rem">{{ tr($p['texto']) }}</h2>
              <div class="diag__opts">
                @foreach ($p['opciones'] as $oi => $o)
                  <div class="diag__opt">
                    <input type="radio" id="q-{{ $p['id'] }}-{{ $oi }}" name="respuestas[{{ $p['id'] }}]" value="{{ $oi }}" required />
                    <label for="q-{{ $p['id'] }}-{{ $oi }}">{{ tr($o['texto']) }}</label>
                  </div>
                @endforeach
              </div>
              <div class="diag__nav">
                @if ($qi > 0)<button type="button" class="btn btn-ghost" data-prev>{{ __('site.diagnostico.atras') }}</button>@else<span></span>@endif
                <button type="button" class="btn btn-primary" data-next>{{ __('site.diagnostico.siguiente') }}</button>
              </div>
            </fieldset>
          @endforeach

          <fieldset class="diag__step">
            <legend class="diag__num">{{ __('site.diagnostico.pregunta') }} {{ count($preguntas) + 1 }} {{ __('site.diagnostico.de') }} {{ count($preguntas) + 1 }}</legend>
            <h2 class="h3" style="font-size:1.35rem">{{ __('site.diagnostico.datos_titulo') }}</h2>
            <p class="small">{{ __('site.diagnostico.datos_sub') }}</p>
            @include('partials.lead-fields', ['full' => false, 'company' => true])
            <div class="diag__nav">
              <button type="button" class="btn btn-ghost" data-prev>{{ __('site.diagnostico.atras') }}</button>
              <button type="submit" class="btn btn-grad">{{ __('site.diagnostico.ver_resultado') }}</button>
            </div>
            <p class="small">{{ __('site.form.privacidad') }}</p>
          </fieldset>
        </form>
      @endif
    </div>
  </section>
@endsection
