@extends('layouts.site')
@section('title'){{ __('site.not_found.titulo') }} · ExperientIA @endsection

@section('content')
  <section class="page-hero" style="min-height:60vh;display:grid;align-items:center">
    <div class="bg-atmos" aria-hidden="true">
      <div class="bg-grid"></div>
      <div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-200px;opacity:.35;"></div>
    </div>
    <div class="container page-hero__inner" style="justify-items:center;text-align:center;max-width:40rem">
      <x-brand-symbol variant="gradient" :size="90" />
      <h1 class="display">404</h1>
      <h2 class="h3">{{ __('site.not_found.titulo') }}</h2>
      <p class="lead">{{ __('site.not_found.sub') }}</p>
      <a href="{{ url('/' . app()->getLocale()) }}" class="btn btn-primary">{{ __('site.not_found.cta') }}</a>
    </div>
  </section>
@endsection
