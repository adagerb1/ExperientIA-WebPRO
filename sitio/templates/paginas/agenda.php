<?php set_meta(t('agenda.meta_title') . ' · ExperientIA', t('agenda.meta_desc')); ?>
<?= hero_pagina(t('agenda.eyebrow'), t('agenda.titulo'), t('agenda.sub')) ?>

<section class="section">
  <div class="container">
    <?php if (empty($slots)): ?>
      <div class="card card-lum form-panel reveal" style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
        <p class="lead"><?= e(t('agenda.sin_horarios')) ?></p>
        <a href="<?= url_pagina('contacto') ?>" class="btn btn-primary"><?= e(t('nav.contacto')) ?></a>
      </div>
    <?php else: ?>
      <form data-api="reserva" data-agenda data-slots="<?= e(json_encode($slots)) ?>" class="agenda__grid">
        <input type="hidden" name="slot" value="" required>
        <input type="hidden" name="timezone" value="">

        <div class="card card-lum form-panel reveal">
          <div>
            <p class="agenda__paso"><?= e(t('agenda.elegir_dia')) ?></p>
            <div class="agenda__dias" data-dias></div>
          </div>
          <div>
            <p class="agenda__paso"><?= e(t('agenda.elegir_hora')) ?></p>
            <div class="agenda__horas" data-horas></div>
            <p class="agenda__zona" data-zona><?= e(t('agenda.zona')) ?></p>
          </div>
          <p class="agenda__resumen" data-resumen hidden></p>
        </div>

        <div class="card card-lum form-panel reveal" style="--reveal-delay:.1s">
          <p class="agenda__paso"><?= e(t('agenda.sus_datos')) ?></p>
          <?= campos_lead(false, true) ?>
          <div class="field">
            <label><?= e(t('agenda.tema')) ?><textarea name="tema" rows="3"></textarea></label>
          </div>
          <button type="submit" class="btn btn-grad" style="justify-self:start" data-loading="<?= e(t('form.enviando')) ?>"><?= e(t('agenda.confirmar')) ?></button>
          <p class="error" data-form-error hidden></p>
          <p class="small"><?= e(t('form.privacidad')) ?></p>
        </div>
      </form>

      <template data-plantilla-ok>
        <div class="card card-lum form-panel" style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
          <span class="icon-chip"><?= icono('check') ?></span>
          <h2 class="h3"><?= e(t('agenda.gracias_titulo')) ?></h2>
          <p><?= e(t('agenda.gracias_sub')) ?></p>
          <a href="<?= url_pagina('home') ?>" class="btn btn-ghost"><?= e(t('not_found.cta')) ?></a>
        </div>
      </template>
    <?php endif; ?>
  </div>
</section>
