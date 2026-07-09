<?php $navItems = ['soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq']; ?>
<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a href="<?= url_pagina('home') ?>" class="site-header__brand" aria-label="<?= e(t('nav.home_label')) ?>">
      <?= logo_marca(false, 36) ?>
    </a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <?php foreach ($navItems as $item): ?>
          <li><a href="<?= url_pagina($item) ?>" <?= pagina_actual() === $item ? 'aria-current="page"' : '' ?>><?= e(t("nav.{$item}")) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="lang-switch" aria-label="<?= e(t('footer.idiomas')) ?>">
        <?php foreach (config('app.locales') as $alt): ?>
          <a href="<?= e(url_cambio_idioma($alt)) ?>" class="<?= locale() === $alt ? 'is-active' : '' ?>" lang="<?= $alt ?>"><?= strtoupper($alt) ?></a>
        <?php endforeach; ?>
      </div>
      <a href="<?= url_pagina('contacto') ?>" class="btn btn-primary site-header__cta"><?= e(t('nav.cta')) ?></a>
    </nav>
    <button class="site-header__toggle" type="button" aria-expanded="false" aria-label="<?= e(t('nav.menu_label')) ?>" data-nav-toggle>
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
