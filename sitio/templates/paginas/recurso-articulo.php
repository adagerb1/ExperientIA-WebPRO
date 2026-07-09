<?php set_meta(tr($recurso['titulo']) . ' · ExperientIA', tr($recurso['extracto'])); ?>
<?= hero_pagina(tr($recurso['tipo_label']), tr($recurso['titulo']), tr($recurso['extracto'])) ?>

<section class="section">
  <div class="container articulo">
    <div class="articulo__cuerpo reveal"><?= tr($recurso['cuerpo']) ?></div>
  </div>
</section>

<?= seccion_cta(t('home.cta_titulo'), t('home.cta_sub'), url_pagina('contacto'), t('home.cta_cta1'), url_pagina('recursos'), t('nav.recursos')) ?>
