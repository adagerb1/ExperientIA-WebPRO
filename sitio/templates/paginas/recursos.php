<?php set_meta(t('recursos.meta_title') . ' · ExperientIA', t('recursos.meta_desc')); ?>
<?= hero_pagina(t('recursos.eyebrow'), t('recursos.titulo'), t('recursos.sub')) ?>

<section class="section">
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($recursos as $i => $r): $esDescarga = $r['type'] === 'download'; ?>
        <article class="card rec__card reveal" style="--reveal-delay:<?= ($i % 3) * 0.08 ?>s">
          <div class="chip-row">
            <span class="icon-chip"><?= icono($esDescarga ? 'doc' : 'eye') ?></span>
            <span class="chip"><?= e(tr($r['tipo_label'])) ?></span>
          </div>
          <h2 class="h3"><?= e(tr($r['titulo'])) ?></h2>
          <p class="rec__texto"><?= e(tr($r['extracto'])) ?></p>
          <a href="<?= url_pagina('recurso', ['slug' => $r['slug']]) ?>" class="link-arrow">
            <?= e($esDescarga ? t('common.descargar') : t('common.leer')) ?> <?= icono('arrow', 16) ?>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section rec-news" id="newsletter">
  <div class="container">
    <div class="card-lum rec-news__panel reveal">
      <div class="bg-atmos" aria-hidden="true">
        <div class="halo halo-cyan" style="width:400px;height:400px;top:-220px;right:-120px;opacity:.4;"></div>
        <div class="halo halo-violet" style="width:380px;height:380px;bottom:-220px;left:-120px;opacity:.35;"></div>
      </div>
      <div class="rec-news__content">
        <h2 class="h2"><?= e(t('recursos.newsletter_titulo')) ?></h2>
        <p class="lead"><?= e(t('recursos.newsletter_sub')) ?></p>
        <form class="rec-news__form" data-api="newsletter" data-ok="<?= e(t('recursos.newsletter_gracias')) ?>">
          <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
          <label class="visually-hidden" for="news-email"><?= e(t('recursos.newsletter_placeholder')) ?></label>
          <input id="news-email" type="email" name="email" placeholder="<?= e(t('recursos.newsletter_placeholder')) ?>" required>
          <button type="submit" class="btn btn-primary"><?= e(t('recursos.newsletter_cta')) ?></button>
        </form>
        <p class="error" data-form-error hidden></p>
        <p class="small"><?= e(t('recursos.newsletter_nota')) ?></p>
      </div>
    </div>
  </div>
</section>
