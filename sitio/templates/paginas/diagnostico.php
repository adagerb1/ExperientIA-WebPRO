<?php set_meta(t('diagnostico.meta_title') . ' · ExperientIA', t('diagnostico.meta_desc')); ?>
<?= hero_pagina(t('diagnostico.eyebrow'), t('diagnostico.titulo'), t('diagnostico.sub')) ?>

<section class="section">
  <div class="container diag">
    <form data-api="diagnostico" data-diag>
      <div class="diag__progress" aria-hidden="true"><i></i></div>

      <?php $total = count($preguntas) + 1; ?>
      <?php foreach ($preguntas as $qi => $p): ?>
        <fieldset class="diag__step">
          <legend class="diag__num"><?= e(t('diagnostico.pregunta')) ?> <?= $qi + 1 ?> <?= e(t('diagnostico.de')) ?> <?= $total ?></legend>
          <h2 class="h3" style="font-size:1.35rem"><?= e(tr($p['texto'])) ?></h2>
          <div class="diag__opts">
            <?php foreach ($p['opciones'] as $oi => $o): ?>
              <div class="diag__opt">
                <input type="radio" id="q-<?= e($p['id']) ?>-<?= $oi ?>" name="respuestas[<?= e($p['id']) ?>]" value="<?= $oi ?>" required>
                <label for="q-<?= e($p['id']) ?>-<?= $oi ?>"><?= e(tr($o['texto'])) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="diag__nav">
            <?php if ($qi > 0): ?><button type="button" class="btn btn-ghost" data-prev><?= e(t('diagnostico.atras')) ?></button><?php else: ?><span></span><?php endif; ?>
            <button type="button" class="btn btn-primary" data-next><?= e(t('diagnostico.siguiente')) ?></button>
          </div>
        </fieldset>
      <?php endforeach; ?>

      <fieldset class="diag__step">
        <legend class="diag__num"><?= e(t('diagnostico.pregunta')) ?> <?= $total ?> <?= e(t('diagnostico.de')) ?> <?= $total ?></legend>
        <h2 class="h3" style="font-size:1.35rem"><?= e(t('diagnostico.datos_titulo')) ?></h2>
        <p class="small"><?= e(t('diagnostico.datos_sub')) ?></p>
        <?= campos_lead(false, true) ?>
        <div class="diag__nav">
          <button type="button" class="btn btn-ghost" data-prev><?= e(t('diagnostico.atras')) ?></button>
          <button type="submit" class="btn btn-grad" data-loading="<?= e(t('form.enviando')) ?>"><?= e(t('diagnostico.ver_resultado')) ?></button>
        </div>
        <p class="error" data-form-error hidden></p>
        <p class="small"><?= e(t('form.privacidad')) ?></p>
      </fieldset>
    </form>

    <div class="card card-lum diag__resultado" data-resultado hidden>
      <p class="eyebrow"><?= e(t('diagnostico.resultado_eyebrow')) ?></p>
      <h2 class="h2"><?= e(t('diagnostico.resultado_titulo')) ?></h2>
      <div class="chip-row" style="justify-content:flex-start; gap:.9rem">
        <span class="icon-chip" data-r-icono></span>
        <span class="chip" data-r-pilar></span>
      </div>
      <h3 class="h3 grad-text" style="font-size:1.6rem" data-r-titulo></h3>
      <p class="lead" data-r-cambia></p>
      <p><?= e(t('diagnostico.resultado_sub')) ?></p>
      <div class="cta-final__actions" style="justify-content:flex-start">
        <a href="<?= url_pagina('agenda') ?>" class="btn btn-primary"><?= e(t('diagnostico.resultado_cta')) ?></a>
        <a href="<?= url_pagina('soluciones') ?>" class="btn btn-ghost"><?= e(t('diagnostico.resultado_cta2')) ?></a>
      </div>
    </div>
  </div>
</section>
