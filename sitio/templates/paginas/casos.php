<?php set_meta(t('casos.meta_title') . ' · ExperientIA', t('casos.meta_desc')); ?>
<?= hero_pagina(t('casos.eyebrow'), t('casos.titulo'), t('casos.sub')) ?>

<section class="section">
  <div class="container casos__list">
    <?php foreach ($casos as $i => $c): $res = json_decode($c['resultados'], true) ?: []; ?>
      <article class="card card-lum caso reveal" style="--reveal-delay:<?= $i * 0.06 ?>s">
        <header>
          <p class="caso__sector"><?= e(tr($c['sector'])) ?></p>
          <h2 class="h2 caso__titulo"><?= e(tr($c['titulo'])) ?></h2>
        </header>
        <div class="caso__body">
          <div><h3 class="caso__label"><?= e(t('common.contexto')) ?></h3><p><?= e(tr($c['contexto'])) ?></p></div>
          <div><h3 class="caso__label"><?= e(t('common.intervencion')) ?></h3><p><?= e(tr($c['intervencion'])) ?></p></div>
          <div class="caso__col--res">
            <h3 class="caso__label caso__label--accent"><?= e(t('common.resultados')) ?></h3>
            <ul class="caso__resultados">
              <?php foreach ($res as $r): ?>
                <li><span class="caso__valor grad-text"><?= e($r['valor']) ?></span><span class="caso__res-label"><?= e(tr($r['label'])) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
    <p class="small casos__nota reveal"><?= e(t('casos.nota')) ?></p>
  </div>
</section>

<?= seccion_cta(t('home.cta_titulo'), t('home.cta_sub'), url_pagina('contacto'), t('home.cta_cta1'), url_pagina('soluciones'), t('common.ver_soluciones')) ?>
