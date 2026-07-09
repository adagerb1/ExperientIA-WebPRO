<section class="hero">
  <div class="bg-atmos" aria-hidden="true">
    <div class="bg-grid"></div>
    <div class="halo halo-cyan anim-pulse" style="width:640px;height:640px;top:-300px;right:-180px;"></div>
    <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-200px;left:-220px;animation-delay:2.6s;"></div>
    <div class="bg-skyline"></div>
  </div>
  <div class="container hero__inner">
    <div class="hero__copy">
      <p class="eyebrow reveal"><?= e(t('home.hero_eyebrow')) ?></p>
      <h1 class="display reveal" style="--reveal-delay:.08s">
        <?= e(t('home.hero_titulo1')) ?><br>
        <span class="grad-text"><?= e(t('home.hero_titulo2')) ?></span>
      </h1>
      <p class="lead reveal" style="--reveal-delay:.16s"><?= e(t('home.hero_sub')) ?></p>
      <div class="hero__actions reveal" style="--reveal-delay:.24s">
        <a href="<?= url_pagina('contacto') ?>" class="btn btn-primary"><?= e(t('home.hero_cta1')) ?></a>
        <a href="<?= url_pagina('diagnostico') ?>" class="btn btn-ghost"><?= e(t('common.hacer_diagnostico')) ?></a>
      </div>
      <p class="small reveal" style="--reveal-delay:.32s"><?= e(t('home.hero_nota')) ?></p>
    </div>
    <div class="hero__visual reveal" style="--reveal-delay:.2s"><?= dash_mock('anim-float') ?></div>
  </div>
</section>

<section class="section proof">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('home.proof_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('home.proof_titulo')) ?></h2>
    </div>
    <div class="grid grid-4">
      <?php
      $metricas = [
          ['v' => '+42%', 'l' => ['es' => 'Crecimiento sostenible', 'en' => 'Sustainable growth', 'pt' => 'Crescimento sustentável'], 'd' => ['es' => 'promedio en programas de 12 meses', 'en' => 'average across 12-month programs', 'pt' => 'média em programas de 12 meses']],
          ['v' => '78%', 'l' => ['es' => 'Procesos optimizados', 'en' => 'Processes optimized', 'pt' => 'Processos otimizados'], 'd' => ['es' => 'en operaciones intervenidas con IA', 'en' => 'in operations transformed with AI', 'pt' => 'em operações transformadas com IA']],
          ['v' => '3.2x', 'l' => ['es' => 'Retorno sobre automatización', 'en' => 'Return on automation', 'pt' => 'Retorno sobre automação'], 'd' => ['es' => 'mediana a los 18 meses', 'en' => 'median at 18 months', 'pt' => 'mediana em 18 meses']],
          ['v' => '48 h', 'l' => ['es' => 'De datos a decisión', 'en' => 'From data to decision', 'pt' => 'De dados a decisão'], 'd' => ['es' => 'ciclos de reporte ejecutivo', 'en' => 'executive reporting cycles', 'pt' => 'ciclos de reporte executivo']],
      ];
      foreach ($metricas as $i => $m): ?>
        <article class="card proof__card reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
          <p class="proof__valor grad-text"><?= e($m['v']) ?></p>
          <h3 class="h3"><?= e(tr($m['l'])) ?></h3>
          <p class="small"><?= e(tr($m['d'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="proof__sectores small reveal"><?= e(t('home.proof_sectores')) ?></p>
  </div>
</section>

<section class="section problema">
  <div class="bg-atmos" aria-hidden="true"><div class="halo halo-violet" style="width:480px;height:480px;top:10%;right:-240px;opacity:.3;"></div></div>
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('home.problema_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('home.problema_titulo')) ?></h2>
      <p class="lead reveal" style="--reveal-delay:.16s"><?= e(t('home.problema_sub')) ?></p>
    </div>
    <div class="grid grid-3">
      <?php
      $bloques = [
          ['i' => 'analitica', 't' => ['es' => 'Decisiones sin visibilidad', 'en' => 'Decisions without visibility', 'pt' => 'Decisões sem visibilidade'], 'x' => ['es' => 'Los reportes llegan tarde, fragmentados y en formatos distintos. El comité directivo decide con intuición donde debería decidir con datos.', 'en' => 'Reports arrive late, fragmented and in different formats. The executive committee decides on intuition where it should decide on data.', 'pt' => 'Os relatórios chegam tarde, fragmentados e em formatos diferentes. O comitê executivo decide por intuição onde deveria decidir com dados.']],
          ['i' => 'gear', 't' => ['es' => 'Operación que consume talento', 'en' => 'Operations that consume talent', 'pt' => 'Operação que consome talento'], 'x' => ['es' => 'Procesos manuales y repetitivos absorben horas de los equipos más valiosos. La capacidad estratégica se gasta en tareas operativas.', 'en' => 'Manual, repetitive processes absorb hours from your most valuable teams. Strategic capacity is spent on operational tasks.', 'pt' => 'Processos manuais e repetitivos absorvem horas das equipes mais valiosas. A capacidade estratégica se gasta em tarefas operacionais.']],
          ['i' => 'cube', 't' => ['es' => 'Herramientas sin estrategia', 'en' => 'Tools without strategy', 'pt' => 'Ferramentas sem estratégia'], 'x' => ['es' => 'Licencias y pilotos de IA que se acumulan sin dueño ni métrica. Inversión tecnológica que no aparece en el estado de resultados.', 'en' => 'Licenses and AI pilots pile up with no owner and no metric. Technology investment that never shows up in the P&L.', 'pt' => 'Licenças e pilotos de IA que se acumulam sem dono nem métrica. Investimento em tecnologia que não aparece no resultado.']],
      ];
      foreach ($bloques as $i => $b): ?>
        <article class="card reveal" style="--reveal-delay:<?= $i * 0.1 ?>s">
          <span class="icon-chip"><?= icono($b['i']) ?></span>
          <h3 class="h3"><?= e(tr($b['t'])) ?></h3>
          <p><?= e(tr($b['x'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section tablero-home">
  <div class="container tablero-home__inner">
    <div class="tablero-home__copy">
      <p class="eyebrow reveal"><?= e(t('home.tablero_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('home.tablero_titulo')) ?></h2>
      <p class="lead reveal" style="--reveal-delay:.16s"><?= e(t('home.tablero_sub')) ?></p>
      <a href="<?= url_pagina('tablero') ?>" class="link-arrow reveal" style="--reveal-delay:.3s"><?= e(t('home.tablero_cta')) ?> <?= icono('arrow', 16) ?></a>
    </div>
    <div class="reveal" style="--reveal-delay:.15s"><?= dash_mock() ?></div>
  </div>
</section>

<section class="section soluciones-home">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('home.soluciones_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('home.soluciones_titulo')) ?></h2>
      <p class="lead reveal" style="--reveal-delay:.16s"><?= e(t('home.soluciones_sub')) ?></p>
    </div>
    <div class="grid grid-2">
      <?php foreach ($soluciones as $i => $s): ?>
        <article class="card card-lum reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
          <div class="chip-row">
            <span class="icon-chip"><?= icono($s['icon']) ?></span>
            <span class="chip"><?= e(tr($s['pilar'])) ?></span>
          </div>
          <h3 class="h3"><?= e(tr($s['titulo'])) ?></h3>
          <p><?= e(tr($s['cambia'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="section-foot reveal"><a href="<?= url_pagina('soluciones') ?>" class="btn btn-ghost"><?= e(t('home.soluciones_cta')) ?></a></div>
  </div>
</section>

<section class="section casos-home">
  <div class="bg-atmos" aria-hidden="true"><div class="halo halo-cyan" style="width:460px;height:460px;bottom:-200px;left:-220px;opacity:.28;"></div></div>
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('home.casos_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('home.casos_titulo')) ?></h2>
      <p class="lead reveal" style="--reveal-delay:.16s"><?= e(t('home.casos_sub')) ?></p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($casos as $i => $c): $res = json_decode($c['resultados'], true) ?: []; ?>
        <article class="card reveal" style="--reveal-delay:<?= $i * 0.1 ?>s">
          <p class="sector-label"><?= e(tr($c['sector'])) ?></p>
          <h3 class="h3"><?= e(tr($c['titulo'])) ?></h3>
          <?php if ($res): ?>
            <p class="metric-big grad-text"><?= e($res[0]['valor']) ?></p>
            <p class="small"><?= e(tr($res[0]['label'])) ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="section-foot reveal">
      <a href="<?= url_pagina('casos') ?>" class="link-arrow"><?= e(t('home.casos_cta')) ?> <?= icono('arrow', 16) ?></a>
    </div>
  </div>
</section>

<?= seccion_cta(t('home.cta_titulo'), t('home.cta_sub'), url_pagina('contacto'), t('home.cta_cta1'), url_pagina('diagnostico'), t('home.cta_cta2')) ?>
