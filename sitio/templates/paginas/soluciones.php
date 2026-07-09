<?php set_meta(t('soluciones.meta_title') . ' · ExperientIA', t('soluciones.meta_desc')); ?>
<?= hero_pagina(t('soluciones.eyebrow'), t('soluciones.titulo'), t('soluciones.sub')) ?>

<section class="section">
  <div class="container sol-list__grid">
    <?php foreach ($soluciones as $i => $s): ?>
      <article class="card card-lum sol-item reveal" style="--reveal-delay:<?= ($i % 2) * 0.08 ?>s">
        <header class="sol-item__head">
          <span class="icon-chip"><?= icono($s['icon']) ?></span>
          <div>
            <p class="sol-item__pilar"><?= e(tr($s['pilar'])) ?></p>
            <h2 class="h3"><?= e(tr($s['titulo'])) ?></h2>
          </div>
        </header>
        <div>
          <h3 class="sol-item__label"><?= e(t('common.problema_label')) ?></h3>
          <p><?= e(tr($s['problema'])) ?></p>
        </div>
        <div>
          <h3 class="sol-item__label"><?= e(t('common.como_label')) ?></h3>
          <ul class="sol-item__list">
            <?php foreach (tr_lines($s['como']) as $linea): ?>
              <li><span class="check-chip"><?= icono('check', 13) ?></span><?= e($linea) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="sol-item__block--cambia">
          <h3 class="sol-item__label sol-item__label--accent"><?= e(t('common.cambia_label')) ?></h3>
          <p><?= e(tr($s['cambia'])) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section metodo">
  <div class="bg-atmos" aria-hidden="true"><div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-240px;opacity:.3;"></div></div>
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('soluciones.metodo_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('soluciones.metodo_titulo')) ?></h2>
    </div>
    <ol class="metodo__steps">
      <?php foreach (t('soluciones.metodo') as $i => $paso): ?>
        <li class="card reveal" style="--reveal-delay:<?= $i * 0.1 ?>s">
          <span class="metodo__num grad-text"><?= e($paso['num']) ?></span>
          <h3 class="h3"><?= e($paso['titulo']) ?></h3>
          <p><?= e($paso['texto']) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<?= seccion_cta(t('home.cta_titulo'), t('home.cta_sub'), url_pagina('contacto'), t('home.cta_cta1'), url_pagina('diagnostico'), t('common.hacer_diagnostico')) ?>
