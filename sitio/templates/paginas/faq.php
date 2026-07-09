<?php
$schemaFaq = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($f) => [
        '@type' => 'Question',
        'name' => tr($f['pregunta']),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => tr($f['respuesta'])],
    ], $faqs),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
set_meta(t('faq.meta_title') . ' · ExperientIA', t('faq.meta_desc'),
    '<script type="application/ld+json">' . $schemaFaq . '</script>');
?>
<?= hero_pagina(t('faq.eyebrow'), t('faq.titulo'), t('faq.sub')) ?>

<section class="section">
  <div class="container">
    <div class="faq-list">
      <?php foreach ($faqs as $i => $f): ?>
        <details class="faq-item reveal" style="--reveal-delay:<?= min($i * 0.05, 0.4) ?>s" <?= $i === 0 ? 'open' : '' ?>>
          <summary><?= e(tr($f['pregunta'])) ?></summary>
          <p><?= e(tr($f['respuesta'])) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= seccion_cta(t('faq.cta_titulo'), t('faq.cta_sub'), url_pagina('agenda'), t('common.agendar_sesion'), url_pagina('contacto'), t('nav.contacto')) ?>
