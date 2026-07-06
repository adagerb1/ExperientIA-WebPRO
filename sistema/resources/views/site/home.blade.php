@extends('layouts.site')

@section('content')
  <section class="hero">
    <div class="bg-atmos" aria-hidden="true">
      <div class="bg-grid"></div>
      <div class="halo halo-cyan anim-pulse" style="width:640px;height:640px;top:-300px;right:-180px;"></div>
      <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-200px;left:-220px;animation-delay:2.6s;"></div>
      <div class="bg-skyline"></div>
    </div>
    <div class="container hero__inner">
      <div class="hero__copy">
        <p class="eyebrow reveal">{{ __('site.home.hero_eyebrow') }}</p>
        <h1 class="display reveal" style="--reveal-delay:.08s">
          {{ __('site.home.hero_titulo1') }}<br />
          <span class="grad-text">{{ __('site.home.hero_titulo2') }}</span>
        </h1>
        <p class="lead reveal" style="--reveal-delay:.16s">{{ __('site.home.hero_sub') }}</p>
        <div class="hero__actions reveal" style="--reveal-delay:.24s">
          <a href="{{ lroute('contacto') }}" class="btn btn-primary">{{ __('site.home.hero_cta1') }}</a>
          <a href="{{ lroute('diagnostico') }}" class="btn btn-ghost">{{ __('site.common.hacer_diagnostico') }}</a>
        </div>
        <p class="small reveal" style="--reveal-delay:.32s">{{ __('site.home.hero_nota') }}</p>
      </div>
      <div class="hero__visual reveal" style="--reveal-delay:.2s">
        <x-dashboard-mock class="anim-float" />
      </div>
    </div>
  </section>

  <section class="section proof">
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.home.proof_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.home.proof_titulo') }}</h2>
      </div>
      <div class="grid grid-4">
        @foreach ([
          ['valor' => '+42%', 'label' => ['es' => 'Crecimiento sostenible', 'en' => 'Sustainable growth', 'pt' => 'Crescimento sustentável'], 'det' => ['es' => 'promedio en programas de 12 meses', 'en' => 'average across 12-month programs', 'pt' => 'média em programas de 12 meses']],
          ['valor' => '78%', 'label' => ['es' => 'Procesos optimizados', 'en' => 'Processes optimized', 'pt' => 'Processos otimizados'], 'det' => ['es' => 'en operaciones intervenidas con IA', 'en' => 'in operations transformed with AI', 'pt' => 'em operações transformadas com IA']],
          ['valor' => '3.2x', 'label' => ['es' => 'Retorno sobre automatización', 'en' => 'Return on automation', 'pt' => 'Retorno sobre automação'], 'det' => ['es' => 'mediana a los 18 meses', 'en' => 'median at 18 months', 'pt' => 'mediana em 18 meses']],
          ['valor' => '48 h', 'label' => ['es' => 'De datos a decisión', 'en' => 'From data to decision', 'pt' => 'De dados a decisão'], 'det' => ['es' => 'ciclos de reporte ejecutivo', 'en' => 'executive reporting cycles', 'pt' => 'ciclos de reporte executivo']],
        ] as $i => $m)
          <article class="card proof__card reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
            <p class="proof__valor grad-text">{{ $m['valor'] }}</p>
            <h3 class="h3">{{ tr($m['label']) }}</h3>
            <p class="small">{{ tr($m['det']) }}</p>
          </article>
        @endforeach
      </div>
      <p class="proof__sectores small reveal">{{ __('site.home.proof_sectores') }}</p>
    </div>
  </section>

  <section class="section problema">
    <div class="bg-atmos" aria-hidden="true">
      <div class="halo halo-violet" style="width:480px;height:480px;top:10%;right:-240px;opacity:.3;"></div>
    </div>
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.home.problema_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.home.problema_titulo') }}</h2>
        <p class="lead reveal" style="--reveal-delay:.16s">{{ __('site.home.problema_sub') }}</p>
      </div>
      <div class="grid grid-3">
        @foreach ([
          ['icon' => 'analitica', 'titulo' => ['es' => 'Decisiones sin visibilidad', 'en' => 'Decisions without visibility', 'pt' => 'Decisões sem visibilidade'], 'texto' => ['es' => 'Los reportes llegan tarde, fragmentados y en formatos distintos. El comité directivo decide con intuición donde debería decidir con datos.', 'en' => 'Reports arrive late, fragmented and in different formats. The executive committee decides on intuition where it should decide on data.', 'pt' => 'Os relatórios chegam tarde, fragmentados e em formatos diferentes. O comitê executivo decide por intuição onde deveria decidir com dados.']],
          ['icon' => 'gear', 'titulo' => ['es' => 'Operación que consume talento', 'en' => 'Operations that consume talent', 'pt' => 'Operação que consome talento'], 'texto' => ['es' => 'Procesos manuales y repetitivos absorben horas de los equipos más valiosos. La capacidad estratégica se gasta en tareas operativas.', 'en' => 'Manual, repetitive processes absorb hours from your most valuable teams. Strategic capacity is spent on operational tasks.', 'pt' => 'Processos manuais e repetitivos absorvem horas das equipes mais valiosas. A capacidade estratégica se gasta em tarefas operacionais.']],
          ['icon' => 'cube', 'titulo' => ['es' => 'Herramientas sin estrategia', 'en' => 'Tools without strategy', 'pt' => 'Ferramentas sem estratégia'], 'texto' => ['es' => 'Licencias y pilotos de IA que se acumulan sin dueño ni métrica. Inversión tecnológica que no aparece en el estado de resultados.', 'en' => 'Licenses and AI pilots pile up with no owner and no metric. Technology investment that never shows up in the P&L.', 'pt' => 'Licenças e pilotos de IA que se acumulam sem dono nem métrica. Investimento em tecnologia que não aparece no resultado.']],
        ] as $i => $b)
          <article class="card reveal" style="--reveal-delay:{{ $i * 0.1 }}s">
            <span class="icon-chip"><x-ei-icon :name="$b['icon']" /></span>
            <h3 class="h3">{{ tr($b['titulo']) }}</h3>
            <p>{{ tr($b['texto']) }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section tablero-home">
    <div class="container tablero-home__inner">
      <div class="tablero-home__copy">
        <p class="eyebrow reveal">{{ __('site.home.tablero_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.home.tablero_titulo') }}</h2>
        <p class="lead reveal" style="--reveal-delay:.16s">{{ __('site.home.tablero_sub') }}</p>
        <a href="{{ lroute('tablero') }}" class="link-arrow reveal" style="--reveal-delay:.3s">
          {{ __('site.home.tablero_cta') }}
          <x-ei-icon name="arrow" :size="16" />
        </a>
      </div>
      <div class="reveal" style="--reveal-delay:.15s">
        <x-dashboard-mock />
      </div>
    </div>
  </section>

  <section class="section soluciones-home">
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.home.soluciones_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.home.soluciones_titulo') }}</h2>
        <p class="lead reveal" style="--reveal-delay:.16s">{{ __('site.home.soluciones_sub') }}</p>
      </div>
      <div class="grid grid-2">
        @foreach ($solutions as $i => $s)
          <article class="card card-lum reveal" style="--reveal-delay:{{ $i * 0.08 }}s">
            <div class="chip-row">
              <span class="icon-chip"><x-ei-icon :name="$s->icon" /></span>
              <span class="chip">{{ tr($s->pilar) }}</span>
            </div>
            <h3 class="h3">{{ tr($s->titulo) }}</h3>
            <p>{{ tr($s->cambia) }}</p>
          </article>
        @endforeach
      </div>
      <div class="section-foot reveal">
        <a href="{{ lroute('soluciones') }}" class="btn btn-ghost">{{ __('site.home.soluciones_cta') }}</a>
      </div>
    </div>
  </section>

  <section class="section casos-home">
    <div class="bg-atmos" aria-hidden="true">
      <div class="halo halo-cyan" style="width:460px;height:460px;bottom:-200px;left:-220px;opacity:.28;"></div>
    </div>
    <div class="container">
      <div class="section-head">
        <p class="eyebrow reveal">{{ __('site.home.casos_eyebrow') }}</p>
        <h2 class="h2 reveal" style="--reveal-delay:.08s">{{ __('site.home.casos_titulo') }}</h2>
        <p class="lead reveal" style="--reveal-delay:.16s">{{ __('site.home.casos_sub') }}</p>
      </div>
      <div class="grid grid-3">
        @foreach ($cases as $i => $c)
          <article class="card reveal" style="--reveal-delay:{{ $i * 0.1 }}s">
            <p class="sector-label">{{ tr($c->sector) }}</p>
            <h3 class="h3">{{ tr($c->titulo) }}</h3>
            @if ($r = $c->resultados[0] ?? null)
              <p class="metric-big grad-text">{{ $r['valor'] }}</p>
              <p class="small">{{ tr($r['label']) }}</p>
            @endif
          </article>
        @endforeach
      </div>
      <div class="section-foot reveal">
        <a href="{{ lroute('casos') }}" class="link-arrow">
          {{ __('site.home.casos_cta') }}
          <x-ei-icon name="arrow" :size="16" />
        </a>
      </div>
    </div>
  </section>

  <x-section-cta
    :titulo="__('site.home.cta_titulo')"
    :sub="__('site.home.cta_sub')"
    :primaryHref="lroute('contacto')"
    :primaryLabel="__('site.home.cta_cta1')"
    :secondaryHref="lroute('diagnostico')"
    :secondaryLabel="__('site.home.cta_cta2')"
  />
@endsection
