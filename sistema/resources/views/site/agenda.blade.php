@extends('layouts.site')
@section('title'){{ __('site.agenda.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.agenda.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.agenda.eyebrow')" :titulo="__('site.agenda.titulo')" :sub="__('site.agenda.sub')" />

  <section class="section">
    <div class="container">
      @if (request('ok'))
        <div class="card card-lum form-panel reveal" style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
          <span class="icon-chip"><x-ei-icon name="check" /></span>
          <h2 class="h3">{{ __('site.agenda.gracias_titulo') }}</h2>
          <p>{{ __('site.agenda.gracias_sub') }}</p>
          <a href="{{ lroute('home') }}" class="btn btn-ghost">{{ __('site.not_found.cta') }}</a>
        </div>
      @elseif (empty($slots))
        <div class="card card-lum form-panel reveal" style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
          <p class="lead">{{ __('site.agenda.sin_horarios') }}</p>
          <a href="{{ lroute('contacto') }}" class="btn btn-primary">{{ __('site.nav.contacto') }}</a>
        </div>
      @else
        <form method="post" action="{{ lroute('agenda.reservar') }}" data-agenda data-slots="{{ json_encode($slots) }}" class="agenda__grid">
          @csrf
          <input type="hidden" name="slot" value="" required />
          <input type="hidden" name="timezone" value="" />

          <div class="card card-lum form-panel reveal">
            <div>
              <p class="agenda__paso">{{ __('site.agenda.elegir_dia') }}</p>
              <div class="agenda__dias" data-dias></div>
            </div>
            <div>
              <p class="agenda__paso">{{ __('site.agenda.elegir_hora') }}</p>
              <div class="agenda__horas" data-horas></div>
              <p class="agenda__zona" data-zona>{{ __('site.agenda.zona') }}</p>
            </div>
            <p class="agenda__resumen" data-resumen hidden></p>
            @error('slot')<p class="error">{{ $message }}</p>@enderror
          </div>

          <div class="card card-lum form-panel reveal" style="--reveal-delay:.1s">
            <p class="agenda__paso">{{ __('site.agenda.sus_datos') }}</p>
            @include('partials.lead-fields', ['full' => false, 'company' => true])
            <div class="field">
              <label for="f-tema">{{ __('site.agenda.tema') }}</label>
              <textarea id="f-tema" name="tema" rows="3">{{ old('tema') }}</textarea>
            </div>
            <button type="submit" class="btn btn-grad" style="justify-self:start">{{ __('site.agenda.confirmar') }}</button>
            <p class="small">{{ __('site.form.privacidad') }}</p>
          </div>
        </form>
      @endif
    </div>
  </section>
@endsection
