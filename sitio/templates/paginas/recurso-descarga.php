<?php set_meta(tr($recurso['titulo']) . ' · ExperientIA', tr($recurso['extracto'])); ?>
<?= hero_pagina(tr($recurso['tipo_label']), tr($recurso['titulo']), tr($recurso['extracto'])) ?>

<section class="section">
  <div class="container contacto__grid">
    <aside>
      <ul class="beneficios">
        <li class="card reveal"><span class="icon-chip"><?= icono('doc') ?></span><p><?= e(tr($recurso['extracto'])) ?></p></li>
        <li class="card reveal" style="--reveal-delay:.08s"><span class="icon-chip"><?= icono('shield') ?></span><p><?= e(t('form.privacidad')) ?></p></li>
      </ul>
    </aside>

    <form class="card card-lum form-panel reveal" data-api="descarga" style="--reveal-delay:.12s">
      <input type="hidden" name="slug" value="<?= e($recurso['slug']) ?>">
      <h2 class="h3"><?= e(t('recursos.gated_titulo')) ?></h2>
      <p class="small"><?= e(t('recursos.gated_sub')) ?></p>
      <?= campos_lead(false, true) ?>
      <button type="submit" class="btn btn-grad" style="justify-self:start" data-loading="<?= e(t('form.enviando')) ?>"><?= e(t('common.descargar')) ?></button>
      <p class="error" data-form-error hidden></p>
      <p class="small"><?= e(t('form.privacidad')) ?></p>
    </form>

    <template data-plantilla-ok>
      <div class="card card-lum form-panel">
        <h2 class="h3"><?= e(t('recursos.descarga_lista')) ?></h2>
        <a href="#" class="btn btn-primary" data-descarga-url><?= e(t('common.descargar')) ?></a>
        <p class="small"><?= e(t('recursos.gated_sub')) ?></p>
      </div>
    </template>
  </div>
</section>
