@extends('layouts.site')
@section('title'){{ __('site.contacto.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.contacto.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.contacto.eyebrow')" :titulo="__('site.contacto.titulo')" :sub="__('site.contacto.sub')" />

  <section class="section" id="gracias">
    <div class="container contacto__grid">
      <aside>
        <ul class="beneficios">
          @foreach (__('site.contacto.beneficios') as $i => $b)
            <li class="card reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
              <span class="icon-chip"><x-ei-icon :name="$b['icon']" /></span>
              <p>{{ $b['texto'] }}</p>
            </li>
          @endforeach
        </ul>
        <ul class="beneficios" style="margin-top:1rem">
          <li class="card reveal" style="--reveal-delay:.3s">
            <span class="icon-chip"><x-ei-icon name="calendar" /></span>
            <p><a href="{{ lroute('agenda') }}">{{ __('site.common.agendar_sesion') }}</a></p>
          </li>
          <li class="card reveal" style="--reveal-delay:.36s">
            <span class="icon-chip"><x-ei-icon name="mail" /></span>
            <p><a href="mailto:{{ config('experientia.contact_email') }}">{{ config('experientia.contact_email') }}</a></p>
          </li>
        </ul>
      </aside>

      @if (request('ok'))
        <div class="card card-lum form-panel reveal">
          <h2 class="h3">{{ __('site.contacto.gracias_titulo') }}</h2>
          <p>{{ __('site.contacto.gracias_sub') }}</p>
          <a href="{{ lroute('agenda') }}" class="btn btn-primary">{{ __('site.common.agendar_sesion') }}</a>
        </div>
      @else
        <form class="card card-lum form-panel reveal" method="post" action="{{ lroute('contacto.enviar') }}" style="--reveal-delay:.12s">
          @csrf
          @include('partials.lead-fields', ['full' => true, 'company' => true])
          <div class="field">
            <label for="f-desafio">{{ __('site.form.desafio') }} *</label>
            <select id="f-desafio" name="desafio" required data-combobox>
              @foreach (__('site.form.desafios') as $key => $label)
                <option value="{{ $key }}" @selected(old('desafio') === $key)>{{ $label }}</option>
              @endforeach
            </select>
            @error('desafio')<p class="error">{{ $message }}</p>@enderror
          </div>
          <div class="field">
            <label for="f-mensaje">{{ __('site.form.mensaje') }}</label>
            <textarea id="f-mensaje" name="mensaje" rows="5">{{ old('mensaje') }}</textarea>
          </div>
          <button type="submit" class="btn btn-grad" style="justify-self:start">{{ __('site.form.enviar') }}</button>
          <p class="small">{{ __('site.form.privacidad') }}</p>
        </form>
      @endif
    </div>
  </section>
@endsection
