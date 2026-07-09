<?php
/** Portal administrativo: login + shell de la SPA (el resto lo hace admin.js contra /api/admin/*). */

function admin_despachar(string $uri, string $metodo): never
{
    if (! admin_actual()) {
        admin_pagina_login();
    }
    admin_pagina_shell();
}

function admin_pagina_login(): never
{
    $csrf = e(csrf_token());
    header('X-Robots-Tag: noindex');
    echo <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso · ExperientIA</title>
  <meta name="robots" content="noindex">
  <meta name="csrf" content="{$csrf}">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="stylesheet" href="/assets/css/estilos.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-login">
  <form class="card card-lum form-panel" id="login">
    <div style="justify-self:center"><img src="/favicon.svg" alt="" width="72" height="56"></div>
    <h1 class="h3" style="text-align:center">Portal ExperientIA</h1>
    <div class="field"><label>Correo<input name="email" type="email" autocomplete="username" required></label></div>
    <div class="field"><label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label></div>
    <button type="submit" class="btn btn-primary">Entrar</button>
    <p class="error" id="login-error" hidden></p>
  </form>
  <script>
    document.getElementById('login').addEventListener('submit', function (ev) {
      ev.preventDefault();
      var f = ev.target;
      fetch('/api/admin/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF': document.querySelector('meta[name=csrf]').content },
        body: JSON.stringify({ email: f.email.value, password: f.password.value })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (j.ok) { location.reload(); return; }
        var e = document.getElementById('login-error');
        e.textContent = j.error || 'Error';
        e.hidden = false;
      });
    });
  </script>
</body>
</html>
HTML;
    exit;
}

function admin_pagina_shell(): never
{
    $csrf = e(csrf_token());
    $admin = e(admin_actual()['name']);
    header('X-Robots-Tag: noindex');
    echo <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel · ExperientIA</title>
  <meta name="robots" content="noindex">
  <meta name="csrf" content="{$csrf}">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="stylesheet" href="/assets/css/estilos.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">
  <aside class="admin__nav">
    <div class="admin__brand"><img src="/favicon.svg" alt="" width="46" height="36"> <b>Experient<span class="grad-text">IA</span></b></div>
    <nav id="nav"></nav>
    <div class="admin__user">
      <span>{$admin}</span>
      <button class="btn btn-ghost" id="salir" type="button">Salir</button>
    </div>
  </aside>
  <main class="admin__main" id="vista"></main>
  <script src="/assets/js/admin.js" defer></script>
</body>
</html>
HTML;
    exit;
}
