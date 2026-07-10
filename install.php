<?php
/**
 * ExperientIA · Instalador de un solo uso.
 *
 *   • Navegador:  abrir  https://tu-dominio/install.php  → asistente (wizard).
 *   • Consola:    php install.php "Nombre" correo@dominio "contraseña"
 *
 * BORRAR este archivo del servidor una vez completada la instalación.
 */

// ─────────────────────────────────────────────────────────────
//  Lógica de instalación compartida (web + CLI)
// ─────────────────────────────────────────────────────────────
function exp_run_install(PDO $pdo, bool $sqlite, ?array $admin = null): array
{
    $log = [];

    // 1) Esquema (adaptado a SQLite en desarrollo)
    $sql = file_get_contents(__DIR__ . '/api/db/schema.sql');
    if ($sqlite) {
        $sql = preg_replace('/ENGINE=InnoDB[^;]*/', '', $sql);
        $sql = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bINT UNSIGNED\b/', 'INTEGER', $sql);
        $sql = preg_replace('/\bBIGINT\b/', 'INTEGER', $sql);
        $sql = preg_replace('/\bTINYINT\b/', 'INTEGER', $sql);
        $sql = preg_replace('/\bJSON\b/', 'TEXT', $sql);
        $sql = preg_replace('/\bMEDIUMTEXT\b/', 'TEXT', $sql);
        $sql = preg_replace('/,\s*INDEX\s+\w+\s*\([^)]*\)/', '', $sql);
    }
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') { $pdo->exec($stmt); }
    }
    $log[] = 'Tablas creadas.';

    // 2) Contenido inicial
    $semillas = require __DIR__ . '/api/db/semillas.php';
    foreach (['solutions', 'products', 'case_studies', 'faqs', 'resources', 'availability_rules'] as $tabla) {
        if ((int) $pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn() > 0) { continue; }
        foreach ($semillas[$tabla] ?? [] as $fila) {
            $cols = implode(',', array_keys($fila));
            $ph = implode(',', array_fill(0, count($fila), '?'));
            $pdo->prepare("INSERT INTO {$tabla} ({$cols}) VALUES ({$ph})")->execute(array_values($fila));
        }
    }
    $log[] = 'Contenido inicial sembrado (soluciones, productos, casos, FAQs, recursos, disponibilidad).';

    // 3) Conectores + plantillas de email
    $extra = require __DIR__ . '/api/db/seed_extra.php';
    foreach ($extra['connectors'] as $prov) {
        $pdo->prepare('INSERT INTO connectors (provider, enabled, status, updated_at) VALUES (?, 0, ?, ?)'
            . ($sqlite ? ' ON CONFLICT(provider) DO NOTHING' : ' ON DUPLICATE KEY UPDATE provider = provider'))
            ->execute([$prov, 'sin_configurar', now_utc()]);
    }
    foreach ($extra['email_templates'] as $tpl) {
        $exists = $pdo->prepare('SELECT COUNT(*) FROM email_templates WHERE tkey = ?');
        $exists->execute([$tpl['tkey']]);
        if ((int) $exists->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO email_templates (tkey, subject, body, active, updated_at) VALUES (?, ?, ?, 1, ?)')
                ->execute([$tpl['tkey'], json_encode($tpl['subject'], JSON_UNESCAPED_UNICODE), json_encode($tpl['body'], JSON_UNESCAPED_UNICODE), now_utc()]);
        }
    }
    $log[] = 'Conectores y plantillas de email registrados.';

    // 4) Administrador propietario
    if ($admin && !empty($admin['name']) && !empty($admin['email']) && !empty($admin['password'])) {
        $correo = mb_strtolower($admin['email']);
        $e = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE email = ?');
        $e->execute([$correo]);
        if ((int) $e->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, 1, ?)')
                ->execute([$admin['name'], $correo, password_hash($admin['password'], PASSWORD_DEFAULT), 'owner', now_utc()]);
            $log[] = "Administrador propietario creado: {$correo}";
        } else {
            $log[] = "El administrador {$correo} ya existía.";
        }
    }

    return $log;
}

// ─────────────────────────────────────────────────────────────
//  Modo CONSOLA (compatibilidad)
// ─────────────────────────────────────────────────────────────
if (PHP_SAPI === 'cli') {
    require __DIR__ . '/api/bootstrap.php';
    require __DIR__ . '/api/helpers.php';
    $pdo = Core\Database::pdo();
    [$_, $nombre, $correo, $clave] = array_pad($argv, 4, null);
    $log = exp_run_install($pdo, Core\Database::isSqlite(), ['name' => $nombre, 'email' => $correo, 'password' => $clave]);
    foreach ($log as $l) { echo "• {$l}\n"; }
    echo "Listo. BORRE install.php del servidor.\n";
    exit;
}

// ─────────────────────────────────────────────────────────────
//  Modo NAVEGADOR (asistente / wizard)
// ─────────────────────────────────────────────────────────────
$envPath = __DIR__ . '/.env';
$errors = [];
$done = null;
$manualEnv = null;

// ¿Ya instalado? (evita re-instalar/hijack)
$yaInstalado = false;
if (is_file($envPath)) {
    try {
        require_once __DIR__ . '/api/Core/Env.php';
        Core\Env::load($envPath);
        require_once __DIR__ . '/api/Core/Database.php';
        $c = Core\Database::run("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ((int) $c > 0) { $yaInstalado = true; }
    } catch (\Throwable $e) { /* aún no instalado: continuar */ }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'experientia.pro';
$appUrlDefault = $scheme . '://' . $host;

$f = fn($k, $d = '') => htmlspecialchars($_POST[$k] ?? $d, ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$yaInstalado) {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $appUrl = rtrim(trim($_POST['app_url'] ?? $appUrlDefault), '/');
    $admName = trim($_POST['adm_name'] ?? '');
    $admMail = trim($_POST['adm_email'] ?? '');
    $admPass = (string) ($_POST['adm_pass'] ?? '');

    if ($dbHost === '' || $dbName === '' || $dbUser === '') { $errors[] = 'Completa los datos de la base de datos (host, nombre y usuario).'; }
    if (!filter_var($appUrl, FILTER_VALIDATE_URL)) { $errors[] = 'La URL del sitio no es válida.'; }
    if ($admName === '' || !filter_var($admMail, FILTER_VALIDATE_EMAIL) || strlen($admPass) < 8) {
        $errors[] = 'Datos del administrador incompletos (nombre, correo válido y contraseña de 8+ caracteres).';
    }

    // Probar conexión a MySQL
    $pdo = null;
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            $errors[] = 'No se pudo conectar a la base de datos: ' . $e->getMessage();
        }
    }

    if (!$errors && $pdo) {
        // Construir .env
        $secret = bin2hex(random_bytes(32));
        $host2 = preg_replace('#^www\.#', '', parse_url($appUrl, PHP_URL_HOST) ?: $host);
        $cors = $appUrl . ',' . $scheme . '://www.' . $host2;
        $env = <<<ENV
        APP_ENV=production
        APP_URL={$appUrl}
        APP_NAME=ExperientIA
        CORS_ALLOWED_ORIGINS={$cors}
        APP_SECRET={$secret}

        DB_DRIVER=mysql
        DB_HOST={$dbHost}
        DB_PORT={$dbPort}
        DB_NAME={$dbName}
        DB_USER={$dbUser}
        DB_PASS={$dbPass}

        MAIL_FROM=hello@{$host2}
        MAIL_FROM_NAME=ExperientIA
        MAIL_NOTIFY=hello@{$host2}

        RATELIMIT_PUBLIC=60
        RATELIMIT_AUTH=180

        # Conectores (se configuran desde el panel · Plataforma → Conectores)
        OPENAI_API_KEY=
        OPENAI_MODEL=gpt-4.1-mini
        SENDGRID_API_KEY=
        TELEGRAM_BOT_COMMERCIAL_TOKEN=
        TELEGRAM_BOT_INTERNAL_TOKEN=
        TELEGRAM_WEBHOOK_SECRET=
        WHATSAPP_TOKEN=
        WHATSAPP_PHONE_ID=
        WHATSAPP_VERIFY_TOKEN=
        WOMPI_PUBLIC_KEY=
        WOMPI_PRIVATE_KEY=
        EPAYCO_PUBLIC_KEY=
        EPAYCO_PRIVATE_KEY=
        STRIPE_SECRET_KEY=
        STRIPE_WEBHOOK_SECRET=
        PAYPAL_CLIENT_ID=
        PAYPAL_SECRET=
        GOOGLE_CLIENT_ID=
        GOOGLE_CLIENT_SECRET=
        GOOGLE_REFRESH_TOKEN=
        GOOGLE_CALENDAR_ID=primary
        ENV;
        $env = preg_replace('/^        /m', '', $env);

        $written = @file_put_contents($envPath, $env) !== false;
        if (!$written) { $manualEnv = $env; }

        try {
            // Ejecutar instalación reutilizando la conexión probada
            require_once __DIR__ . '/api/Core/Env.php';
            Core\Env::load($envPath);
            require_once __DIR__ . '/api/helpers.php';
            $log = exp_run_install($pdo, false, ['name' => $admName, 'email' => $admMail, 'password' => $admPass]);
            $done = ['log' => $log, 'app_url' => $appUrl, 'email' => mb_strtolower($admMail), 'manual' => $manualEnv];
        } catch (\Throwable $e) {
            $errors[] = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}

// ─────────────────────────────────────────────────────────────
//  Chequeo de requisitos
// ─────────────────────────────────────────────────────────────
$req = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'Extensión PDO' => extension_loaded('pdo'),
    'Driver pdo_mysql' => extension_loaded('pdo_mysql'),
    'Extensión mbstring' => extension_loaded('mbstring'),
    'Extensión curl' => extension_loaded('curl'),
    'Carpeta escribible (.env)' => is_writable(__DIR__),
];
$reqOk = !in_array(false, $req, true);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Instalador · ExperientIA</title>
<style>
  :root{ --b1:#051126; --b2:#0a1b3a; --cy:#18d6f1; --vi:#7a63ff; --lt:#f6f8fb; --md:#d9e2f0; --gl:rgba(18,34,66,.55); }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,sans-serif; color:var(--lt);
    background:radial-gradient(1200px 700px at 15% -10%, rgba(122,99,255,.22), transparent 60%),
               radial-gradient(1000px 600px at 100% 0%, rgba(24,214,241,.16), transparent 55%), var(--b1);
    min-height:100vh; padding:40px 16px; }
  .wrap{ max-width:720px; margin:0 auto; }
  .logo{ height:40px; margin-bottom:24px; }
  .card{ background:var(--gl); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.10); border-radius:18px; padding:28px; margin-bottom:20px;
    box-shadow:0 24px 60px rgba(0,0,0,.4); }
  h1{ font-size:1.6rem; margin:0 0 6px; letter-spacing:-.02em; }
  h2{ font-size:1.05rem; margin:0 0 16px; color:var(--cy); letter-spacing:.02em; }
  p.sub{ color:var(--md); margin:0 0 22px; }
  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .field{ display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
  .field.full{ grid-column:1/-1; }
  label{ font-size:.82rem; color:var(--md); }
  input{ background:rgba(5,17,38,.6); border:1px solid rgba(255,255,255,.14); border-radius:10px;
    padding:11px 13px; color:var(--lt); font-size:.95rem; }
  input:focus{ outline:none; border-color:var(--cy); box-shadow:0 0 0 3px rgba(24,214,241,.16); }
  .btn{ display:inline-block; width:100%; border:none; cursor:pointer; border-radius:12px; padding:14px 18px;
    font-size:1rem; font-weight:600; color:#04101f; background:linear-gradient(96deg,var(--cy),var(--vi)); }
  .btn:disabled{ opacity:.5; cursor:not-allowed; }
  ul.req{ list-style:none; padding:0; margin:0; display:grid; gap:8px; }
  ul.req li{ display:flex; align-items:center; gap:10px; font-size:.92rem; }
  .ok{ color:#39f9b0; } .no{ color:#ff7d9d; }
  .badge{ width:20px; height:20px; border-radius:50%; display:grid; place-items:center; font-size:.7rem; font-weight:700; }
  .badge.ok{ background:rgba(57,249,176,.15); } .badge.no{ background:rgba(255,125,157,.15); }
  .alert{ background:rgba(255,125,157,.12); border:1px solid rgba(255,125,157,.3); color:#ffd0da;
    border-radius:12px; padding:14px 16px; margin-bottom:18px; font-size:.9rem; }
  .success{ text-align:center; }
  .success .big{ font-size:2.4rem; margin:6px 0 10px; }
  .loglist{ list-style:none; padding:0; margin:18px 0; display:grid; gap:8px; text-align:left; }
  .loglist li{ background:rgba(5,17,38,.5); border:1px solid rgba(255,255,255,.08); border-radius:10px;
    padding:10px 14px; font-size:.9rem; color:var(--md); }
  .loglist li::before{ content:'✓ '; color:var(--cy); font-weight:700; }
  a.cta{ color:var(--cy); font-weight:600; text-decoration:none; }
  .warn{ background:rgba(255,196,71,.12); border:1px solid rgba(255,196,71,.35); color:#ffe4a3;
    border-radius:12px; padding:14px 16px; margin-top:18px; font-size:.9rem; }
  code{ background:rgba(0,0,0,.4); padding:2px 6px; border-radius:6px; font-size:.85em; }
  pre{ text-align:left; background:rgba(0,0,0,.45); border:1px solid rgba(255,255,255,.1); border-radius:10px;
    padding:14px; overflow:auto; font-size:.78rem; color:var(--md); }
  @media(max-width:560px){ .grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="wrap">
  <img class="logo" src="/assets/img/brand/logo.png" alt="ExperientIA">

  <?php if ($done): ?>
    <div class="card success">
      <h2>Instalación completada</h2>
      <div class="big">🎉</div>
      <p class="sub">Tu plataforma ExperientIA está lista.</p>
      <ul class="loglist"><?php foreach ($done['log'] as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
      <p><a class="cta" href="<?= htmlspecialchars($done['app_url']) ?>/">Ir al sitio →</a> &nbsp;·&nbsp;
         <a class="cta" href="<?= htmlspecialchars($done['app_url']) ?>/admin">Entrar al portal (<?= htmlspecialchars($done['email']) ?>) →</a></p>
      <?php if (!empty($done['manual'])): ?>
        <div class="warn">No se pudo escribir <code>.env</code> automáticamente. Créalo manualmente en la raíz con este contenido:
          <pre><?= htmlspecialchars($done['manual']) ?></pre></div>
      <?php endif; ?>
      <div class="warn"><b>Importante:</b> borra ahora <code>install.php</code> del servidor por seguridad.</div>
    </div>

  <?php elseif ($yaInstalado): ?>
    <div class="card">
      <h1>Ya instalado</h1>
      <p class="sub">La plataforma ya tiene una instalación activa. Por seguridad, borra <code>install.php</code> del servidor.</p>
      <p><a class="cta" href="/admin">Ir al portal admin →</a></p>
    </div>

  <?php else: ?>
    <div class="card">
      <h1>Asistente de instalación</h1>
      <p class="sub">Configura tu plataforma ExperientIA en un paso. Todo queda dentro de <code>public_html</code>.</p>
      <h2>Requisitos del servidor</h2>
      <ul class="req">
        <?php foreach ($req as $name => $ok): ?>
          <li><span class="badge <?= $ok ? 'ok' : 'no' ?>"><?= $ok ? '✓' : '✕' ?></span>
            <span class="<?= $ok ? 'ok' : 'no' ?>"><?= htmlspecialchars($name) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php foreach ($errors as $e): ?><div class="alert"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <form method="post" class="card" autocomplete="off">
      <h2>Base de datos (MySQL)</h2>
      <p class="sub">Créala antes en cPanel → MySQL Databases y pega aquí sus datos.</p>
      <div class="grid">
        <div class="field"><label>Host</label><input name="db_host" value="<?= $f('db_host', 'localhost') ?>" required></div>
        <div class="field"><label>Puerto</label><input name="db_port" value="<?= $f('db_port', '3306') ?>"></div>
        <div class="field"><label>Nombre de la base de datos</label><input name="db_name" value="<?= $f('db_name') ?>" required></div>
        <div class="field"><label>Usuario</label><input name="db_user" value="<?= $f('db_user') ?>" required></div>
        <div class="field full"><label>Contraseña</label><input name="db_pass" type="password" value="<?= $f('db_pass') ?>"></div>
      </div>

      <h2 style="margin-top:10px">Sitio</h2>
      <div class="field"><label>URL pública del sitio</label><input name="app_url" value="<?= $f('app_url', $appUrlDefault) ?>" required></div>

      <h2 style="margin-top:10px">Administrador del portal</h2>
      <div class="grid">
        <div class="field"><label>Nombre</label><input name="adm_name" value="<?= $f('adm_name') ?>" required></div>
        <div class="field"><label>Correo</label><input name="adm_email" type="email" value="<?= $f('adm_email') ?>" required></div>
        <div class="field full"><label>Contraseña (8+ caracteres)</label><input name="adm_pass" type="password" required></div>
      </div>

      <button class="btn" type="submit" <?= $reqOk ? '' : 'disabled' ?>>Instalar ExperientIA</button>
      <?php if (!$reqOk): ?><p class="sub" style="margin-top:12px;text-align:center">Resuelve primero los requisitos marcados en rojo.</p><?php endif; ?>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
