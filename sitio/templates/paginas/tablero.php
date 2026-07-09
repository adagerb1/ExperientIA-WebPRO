<?php set_meta(t('tablero.meta_title') . ' · ExperientIA', t('tablero.meta_desc')); ?>
<?= hero_pagina(t('tablero.eyebrow'), t('tablero.titulo'), t('tablero.sub'),
    '<div class="hero__actions reveal" style="--reveal-delay:.24s"><a href="' . url_pagina('agenda') . '" class="btn btn-primary">' . e(t('tablero.cta')) . '</a></div>') ?>

<section class="tab-visual">
  <div class="container">
    <div class="tab-visual__stage reveal">
      <div class="bg-atmos" aria-hidden="true">
        <div class="halo halo-cyan anim-pulse" style="width:560px;height:560px;top:-180px;left:8%;"></div>
        <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-220px;right:4%;animation-delay:2.2s;"></div>
      </div>
      <?= dash_mock('tab-visual__dash') ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-3">
      <?php
      $pilares = [
          ['i' => 'eye', 't' => ['es' => 'Visibilidad total', 'en' => 'Total visibility', 'pt' => 'Visibilidade total'], 'x' => ['es' => 'Ingresos, eficiencia, clientes y adopción de IA en una sola vista. Sin esperar al cierre de mes.', 'en' => 'Revenue, efficiency, customers and AI adoption in a single view. No waiting for month-end close.', 'pt' => 'Receita, eficiência, clientes e adoção de IA em uma única visão. Sem esperar o fechamento do mês.']],
          ['i' => 'alert', 't' => ['es' => 'Alertas con criterio', 'en' => 'Alerts with judgment', 'pt' => 'Alertas com critério'], 'x' => ['es' => 'El tablero no grita datos: señala qué cambió, por qué importa y qué decisión requiere.', 'en' => 'The board does not shout data: it signals what changed, why it matters and what decision it requires.', 'pt' => 'O painel não grita dados: sinaliza o que mudou, por que importa e que decisão exige.']],
          ['i' => 'shield', 't' => ['es' => 'Gobierno del crecimiento', 'en' => 'Growth governance', 'pt' => 'Governança do crescimento'], 'x' => ['es' => 'Cada indicador tiene dueño, meta y ritual de revisión. La estrategia se ejecuta, no se archiva.', 'en' => 'Every indicator has an owner, a target and a review ritual. Strategy gets executed, not archived.', 'pt' => 'Cada indicador tem dono, meta e ritual de revisão. A estratégia se executa, não se arquiva.']],
      ];
      foreach ($pilares as $i => $p): ?>
        <article class="card card-lum tab-pilar reveal" style="--reveal-delay:<?= $i * 0.1 ?>s">
          <span class="icon-chip"><?= icono($p['i']) ?></span>
          <h2 class="h3"><?= e(tr($p['t'])) ?></h2>
          <p><?= e(tr($p['x'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section tab-features">
  <div class="bg-atmos" aria-hidden="true"><div class="halo halo-violet" style="width:460px;height:460px;top:0;left:-240px;opacity:.28;"></div></div>
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('tablero.features_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('tablero.features_titulo')) ?></h2>
    </div>
    <div class="grid grid-4">
      <?php
      $features = [
          ['i' => 'growth', 't' => ['es' => 'KPIs de negocio en vivo', 'en' => 'Live business KPIs', 'pt' => 'KPIs de negócio ao vivo'], 'x' => ['es' => 'Ventas, margen, cash y eficiencia conectados a la fuente.', 'en' => 'Sales, margin, cash and efficiency connected to the source.', 'pt' => 'Vendas, margem, caixa e eficiência conectados à fonte.']],
          ['i' => 'ia', 't' => ['es' => 'Señales con IA', 'en' => 'AI-powered signals', 'pt' => 'Sinais com IA'], 'x' => ['es' => 'Anomalías y tendencias detectadas antes de que sean problemas.', 'en' => 'Anomalies and trends detected before they become problems.', 'pt' => 'Anomalias e tendências detectadas antes de virarem problemas.']],
          ['i' => 'gear', 't' => ['es' => 'Flujos automatizados', 'en' => 'Automated flows', 'pt' => 'Fluxos automatizados'], 'x' => ['es' => 'Reportes y rituales ejecutivos que se preparan solos.', 'en' => 'Executive reports and rituals that prepare themselves.', 'pt' => 'Relatórios e rituais executivos que se preparam sozinhos.']],
          ['i' => 'people', 't' => ['es' => 'Adopción y cultura', 'en' => 'Adoption & culture', 'pt' => 'Adoção e cultura'], 'x' => ['es' => 'Uso real por equipos: la transformación también se mide.', 'en' => 'Real usage by teams: transformation gets measured too.', 'pt' => 'Uso real pelas equipes: a transformação também se mede.']],
      ];
      foreach ($features as $i => $f): ?>
        <article class="card reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
          <span class="icon-chip"><?= icono($f['i']) ?></span>
          <h3 class="h3" style="font-size:1.05rem"><?= e(tr($f['t'])) ?></h3>
          <p class="small"><?= e(tr($f['x'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= seccion_cta(t('tablero.cta_titulo'), t('tablero.cta_sub'), url_pagina('agenda'), t('tablero.cta'), url_pagina('soluciones'), t('common.ver_soluciones')) ?>
