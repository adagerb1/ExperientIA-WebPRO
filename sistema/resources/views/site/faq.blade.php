@extends('layouts.site')
@section('title'){{ __('site.faq.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.faq.meta_desc') }}@endsection

@section('schema')
  <script type="application/ld+json">{!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => $faqs->map(fn ($f) => [
          '@type' => 'Question',
          'name' => tr($f->pregunta),
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => tr($f->respuesta)],
      ])->values()->all(),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.faq.eyebrow')" :titulo="__('site.faq.titulo')" :sub="__('site.faq.sub')" />

  <section class="section">
    <div class="container">
      <div class="faq-list">
        @foreach ($faqs as $i => $f)
          <details class="faq-item reveal" style="--reveal-delay:{{ min($i * 0.05, 0.4) }}s" @if ($i === 0) open @endif>
            <summary>{{ tr($f->pregunta) }}</summary>
            <p>{{ tr($f->respuesta) }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.faq.cta_titulo')"
    :sub="__('site.faq.cta_sub')"
    :primaryHref="lroute('agenda')"
    :primaryLabel="__('site.common.agendar_sesion')"
    :secondaryHref="lroute('contacto')"
    :secondaryLabel="__('site.nav.contacto')"
  />
@endsection
