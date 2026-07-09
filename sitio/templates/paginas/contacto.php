<?php set_meta(t('contacto.meta_title') . ' · ExperientIA', t('contacto.meta_desc')); ?>
<?= hero_pagina(t('contacto.eyebrow'), t('contacto.titulo'), t('contacto.sub')) ?>

<section class="section">
  <div class="container contacto__grid">
    <aside>
      <ul class="beneficios">
        <?php foreach (t('contacto.beneficios') as $i => $b): ?>
          <li class="card reveal" style="--reveal-delay:<?= $i * 0.08 ?>s">
            <span class="icon-chip"><?= icono($b['icon']) ?></span>
            <p><?= e($b['texto']) ?></p>
          </li>
        <?php endforeach; ?>
        <li class="card reveal" style="--reveal-delay:.3s">
          <span class="icon-chip"><?= icono('calendar') ?></span>
          <p><a href="<?= url_pagina('agenda') ?>"><?= e(t('common.agendar_sesion')) ?></a></p>
        </li>
        <li class="card reveal" style="--reveal-delay:.36s">
          <span class="icon-chip"><?= icono('mail') ?></span>
          <p><a href="mailto:<?= e(config('mail.from')) ?>"><?= e(config('mail.from')) ?></a></p>
        </li>
      </ul>
    </aside>

    <form class="card card-lum form-panel reveal" data-api="contacto" style="--reveal-delay:.12s">
      <?= campos_lead(true, true) ?>
      <div class="field">
        <label><?= e(t('form.desafio')) ?> *
          <select name="desafio" required data-combobox>
            <?php foreach (t('form.desafios') as $k => $v): ?>
              <option value="<?= e($k) ?>"><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="field">
        <label><?= e(t('form.mensaje')) ?><textarea name="mensaje" rows="5"></textarea></label>
      </div>
      <button type="submit" class="btn btn-grad" style="justify-self:start" data-loading="<?= e(t('form.enviando')) ?>"><?= e(t('form.enviar')) ?></button>
      <p class="error" data-form-error hidden></p>
      <p class="small"><?= e(t('form.privacidad')) ?></p>
    </form>

    <template data-plantilla-ok>
      <div class="card card-lum form-panel">
        <h2 class="h3"><?= e(t('contacto.gracias_titulo')) ?></h2>
        <p><?= e(t('contacto.gracias_sub')) ?></p>
        <a href="<?= url_pagina('agenda') ?>" class="btn btn-primary"><?= e(t('common.agendar_sesion')) ?></a>
      </div>
    </template>
  </div>
</section>
