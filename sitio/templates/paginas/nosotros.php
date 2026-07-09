<?php set_meta(t('nosotros.meta_title') . ' · ExperientIA', t('nosotros.meta_desc')); ?>
<?= hero_pagina(t('nosotros.eyebrow'), t('nosotros.titulo'), t('nosotros.sub')) ?>

<section class="section valores">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('nosotros.esencia_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('nosotros.esencia_titulo')) ?></h2>
    </div>
    <div class="grid grid-4">
      <?php foreach (t('nosotros.valores') as $i => $v): ?>
        <article class="card reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
          <span class="icon-chip"><?= icono($v['icon']) ?></span>
          <h3 class="h3" style="font-size:1.05rem"><?= e($v['titulo']) ?></h3>
          <p class="small"><?= e($v['texto']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section nos-pers">
  <div class="bg-atmos" aria-hidden="true"><div class="halo halo-violet" style="width:460px;height:460px;top:-140px;left:-240px;opacity:.3;"></div></div>
  <div class="container">
    <div class="section-head">
      <p class="eyebrow reveal"><?= e(t('nosotros.personalidad_eyebrow')) ?></p>
      <h2 class="h2 reveal" style="--reveal-delay:.08s"><?= e(t('nosotros.personalidad_titulo')) ?></h2>
    </div>
    <div class="nos-pers__grid">
      <div class="nos-pers__symbol reveal" aria-hidden="true"><?= simbolo_marca('gradient', 150, 'anim-float') ?></div>
      <div class="nos-pers__rasgos">
        <?php foreach (t('nosotros.rasgos') as $i => $r): ?>
          <div class="nos-pers__rasgo reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
            <h3 class="h3 grad-text"><?= e($r['titulo']) ?></h3>
            <p><?= e($r['texto']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section nos-eco">
  <div class="container">
    <div class="card-lum nos-eco__panel reveal">
      <div class="bg-atmos" aria-hidden="true"><div class="bg-skyline"></div><div class="halo halo-cyan" style="width:420px;height:420px;top:-240px;right:-100px;opacity:.35;"></div></div>
      <div class="nos-eco__content">
        <p class="eyebrow"><?= e(t('nosotros.eco_eyebrow')) ?></p>
        <h2 class="h2"><?= e(t('nosotros.eco_titulo')) ?></h2>
        <p class="lead"><?= e(t('nosotros.eco_texto')) ?></p>
        <a href="https://tonnydager.com" class="link-arrow" rel="noopener" target="_blank"><?= e(t('nosotros.eco_cta')) ?> <?= icono('arrow', 16) ?></a>
      </div>
    </div>
  </div>
</section>

<?= seccion_cta(t('nosotros.cta_titulo'), t('nosotros.cta_sub'), url_pagina('agenda'), t('nosotros.cta_cta')) ?>
