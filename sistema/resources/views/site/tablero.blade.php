@extends('layouts.site')
@section('title'){{ __('site.tablero.meta_title') }} · ExperientIA @endsection
@section('description'){{ __('site.tablero.meta_desc') }}@endsection

@section('content')
  <x-page-hero :eyebrow="__('site.tablero.eyebrow')" :titulo="__('site.tablero.titulo')" :sub="__('site.tablero.sub')">
    <div class="hero__actions reveal" style="--reveal-delay:.24s">
      <a href="{{ lroute('agenda') }}" class="btn btn-primary">{{ __('site.tablero.cta') }}</a>
    </div>
  </x-page-hero>

  <section class="tab-visual">
    <div class="container">
      <div class="tab-visual__stage reveal">
        <div class="bg-atmos" aria-hidden="true">
          <div class="halo halo-cyan anim-pulse" style="width:560px;height:560px;top:-180px;left:8%;"></div>
          <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-220px;right:4%;animation-delay:2.2s;"></div>
        </div>
        <x-dashboard-mock class="tab-visual__dash" />
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="grid grid-3">
        @foreach ([
          ['icon' => 'eye', 'titulo' => ['es' => 'Visibilidad total', 'en' => 'Total visibility', 'pt' => 'Visibilidade total'], 'texto' => ['es' => 'Ingresos, eficiencia, clientes y adopción de IA en una sola vista. Sin esperar al cierre de mes.', 'en' => 'Revenue, efficiency, customers and AI adoption in a single view. No waiting for month-end close.', 'pt' => 'Receita, eficiência, clientes e adoção de IA em uma única visão. Sem esperar o fechamento do mês.']],
          ['icon' => 'alert', 'titulo' => ['es' => 'Alertas con criterio', 'en' => 'Alerts with judgment', 'pt' => 'Alertas com critério'], 'texto' => ['es' => 'El tablero no grita datos: señala qué cambió, por qué importa y qué decisión requiere.', 'en' => 'The board does not shout data: it signals what changed, why it matters and what decision it requires.', 'pt' => 'O painel não grita dados: sinaliza o que mudou, por que importa e que decisão exige.']],
          ['icon' => 'shield', 'titulo' => ['es' => 'Gobierno del crecimiento', 'en' => 'Growth governance', 'pt' => 'Governança do crescimento'], 'texto' => ['es' => 'Cada indicador tiene dueño, meta y ritual de revisión. La estrategia se ejecuta, no se archiva.', 'en' => 'Every indicator has an owner, a target and a review ritual. Strategy gets executed, not archived.', 'pt' => 'Cada indicador tem dono, meta e ritual de revisão. A estratégia se executa, não se arquiva.']],
        ] as $i => $p)
          <article class="card card-lum tab-pilar reveal" style="--reveal-delay:{{ $i * 0.1 }}s">
            <span class="icon-chip"><x-ei-icon :name="$p['icon']" /></span>
            <h2 class="h3">{{ tr($p['titulo']) }}</h2>
            <p>{{ tr($p['texto']) }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section tab-features">
    <div class="bg-atmos" aria-hidden="true">
      <div class="halo halo-violet" style="width:460px;height:460px;top:0;left:-240px;opacity:.28;"></div>
    </div>
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.tablero.features_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.tablero.features_titulo') }}</h2>
      </div>
      <div class="grid grid-4">
        @foreach ([
          ['icon' => 'growth', 'titulo' => ['es' => 'KPIs de negocio en vivo', 'en' => 'Live business KPIs', 'pt' => 'KPIs de negócio ao vivo'], 'texto' => ['es' => 'Ventas, margen, cash y eficiencia conectados a la fuente.', 'en' => 'Sales, margin, cash and efficiency connected to the source.', 'pt' => 'Vendas, margem, caixa e eficiência conectados à fonte.']],
          ['icon' => 'ia', 'titulo' => ['es' => 'Señales con IA', 'en' => 'AI-powered signals', 'pt' => 'Sinais com IA'], 'texto' => ['es' => 'Anomalías y tendencias detectadas antes de que sean problemas.', 'en' => 'Anomalies and trends detected before they become problems.', 'pt' => 'Anomalias e tendências detectadas antes de virarem problemas.']],
          ['icon' => 'gear', 'titulo' => ['es' => 'Flujos automatizados', 'en' => 'Automated flows', 'pt' => 'Fluxos automatizados'], 'texto' => ['es' => 'Reportes y rituales ejecutivos que se preparan solos.', 'en' => 'Executive reports and rituals that prepare themselves.', 'pt' => 'Relatórios e rituais executivos que se preparam sozinhos.']],
          ['icon' => 'people', 'titulo' => ['es' => 'Adopción y cultura', 'en' => 'Adoption & culture', 'pt' => 'Adoção e cultura'], 'texto' => ['es' => 'Uso real por equipos: la transformación también se mide.', 'en' => 'Real usage by teams: transformation gets measured too.', 'pt' => 'Uso real pelas equipes: a transformação também se mede.']],
        ] as $i => $f)
          <article class="card reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
            <span class="icon-chip"><x-ei-icon :name="$f['icon']" /></span>
            <h3 class="h3" style="font-size:1.05rem">{{ tr($f['titulo']) }}</h3>
            <p class="small">{{ tr($f['texto']) }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.tablero.cta_titulo')"
    :sub="__('site.tablero.cta_sub')"
    :primaryHref="lroute('agenda')"
    :primaryLabel="__('site.tablero.cta')"
    :secondaryHref="lroute('soluciones')"
    :secondaryLabel="__('site.common.ver_soluciones')"
  />
@endsection
