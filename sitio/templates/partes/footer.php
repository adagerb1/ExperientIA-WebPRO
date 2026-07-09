<footer class="site-footer">
  <div class="container">
    <hr class="divider">
    <div class="site-footer__grid">
      <div class="site-footer__brand">
        <a href="<?= url_pagina('home') ?>" aria-label="ExperientIA"><?= logo_marca(true, 46) ?></a>
        <p class="site-footer__tagline"><?= e(t('footer.tagline')) ?></p>
        <div class="site-footer__social" aria-label="<?= e(t('footer.seguir')) ?>">
          <?php foreach (config('social') as $s): ?>
            <a href="<?= e($s['url']) ?>" rel="noopener" target="_blank" aria-label="<?= e($s['name']) ?>" title="<?= e($s['name']) ?>"><?= icono($s['icon'], 18) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <nav aria-label="<?= e(t('footer.col_soluciones')) ?>">
        <h2 class="site-footer__title"><?= e(t('footer.col_soluciones')) ?></h2>
        <ul>
          <?php foreach (['soluciones', 'tablero', 'productos'] as $k): ?>
            <li><a href="<?= url_pagina($k) ?>"><?= e(t("nav.{$k}")) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= url_pagina('diagnostico') ?>"><?= e(t('diagnostico.eyebrow')) ?></a></li>
        </ul>
      </nav>
      <nav aria-label="<?= e(t('footer.col_compania')) ?>">
        <h2 class="site-footer__title"><?= e(t('footer.col_compania')) ?></h2>
        <ul>
          <?php foreach (['casos', 'recursos', 'nosotros', 'faq', 'contacto'] as $k): ?>
            <li><a href="<?= url_pagina($k) ?>"><?= e(t("nav.{$k}")) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div>
        <h2 class="site-footer__title"><?= e(t('footer.col_contacto')) ?></h2>
        <ul>
          <li><a href="mailto:<?= e(config('mail.from')) ?>"><?= e(config('mail.from')) ?></a></li>
          <li><a href="<?= url_pagina('agenda') ?>"><?= e(t('common.agendar_sesion')) ?></a></li>
          <li><span class="site-footer__muted"><?= e(t('footer.contacto_linea')) ?></span></li>
        </ul>
        <h2 class="site-footer__title site-footer__title--langs"><?= e(t('footer.idiomas')) ?></h2>
        <div class="lang-switch">
          <?php foreach (config('app.locales') as $alt): ?>
            <a href="<?= e(url_cambio_idioma($alt)) ?>" class="<?= locale() === $alt ? 'is-active' : '' ?>" lang="<?= $alt ?>"><?= strtoupper($alt) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="site-footer__legal">
      <p>© <?= date('Y') ?> ExperientIA. <?= e(t('footer.legal')) ?></p>
      <p><?= e(t('footer.ecosistema')) ?> <a href="https://tonnydager.com" rel="noopener" target="_blank">Tonny Dager</a></p>
    </div>
  </div>
</footer>
