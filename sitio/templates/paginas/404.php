<?php set_meta(t('not_found.titulo') . ' · ExperientIA'); ?>
<section class="page-hero" style="min-height:60vh;display:grid;align-items:center">
  <div class="bg-atmos" aria-hidden="true">
    <div class="bg-grid"></div>
    <div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-200px;opacity:.35;"></div>
  </div>
  <div class="container page-hero__inner" style="justify-items:center;text-align:center;max-width:40rem">
    <?= simbolo_marca('gradient', 90) ?>
    <h1 class="display">404</h1>
    <h2 class="h3"><?= e(t('not_found.titulo')) ?></h2>
    <p class="lead"><?= e(t('not_found.sub')) ?></p>
    <a href="/<?= locale() ?>/" class="btn btn-primary"><?= e(t('not_found.cta')) ?></a>
  </div>
</section>
