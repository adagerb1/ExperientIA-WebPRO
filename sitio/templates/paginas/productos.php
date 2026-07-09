<?php set_meta(t('productos.meta_title') . ' · ExperientIA', t('productos.meta_desc')); ?>
<?= hero_pagina(t('productos.eyebrow'), t('productos.titulo'), t('productos.sub')) ?>

<section class="section">
  <div class="container prod__grid">
    <?php foreach ($productos as $i => $p): ?>
      <article class="card prod__card reveal <?= $p['destacado'] ? 'card-lum prod__card--destacado' : '' ?>" style="--reveal-delay:<?= $i * 0.08 ?>s">
        <div class="chip-row">
          <span class="icon-chip"><?= icono($p['icon']) ?></span>
          <span class="chip chip--cyan"><?= e(tr($p['rol'])) ?></span>
        </div>
        <h2 class="h3"><?= e(tr($p['nombre'])) ?></h2>
        <p><?= e(tr($p['texto'])) ?></p>
        <a href="<?= $p['destacado'] ? url_pagina('tablero') : url_pagina('contacto') ?>" class="link-arrow"><?= e(t('common.conocer_mas')) ?> <?= icono('arrow', 16) ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?= seccion_cta(t('home.cta_titulo'), t('home.cta_sub'), url_pagina('contacto'), t('home.cta_cta1'), url_pagina('casos'), t('nav.casos')) ?>
