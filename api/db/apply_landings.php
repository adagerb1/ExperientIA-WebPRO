<?php
/**
 * Aplica la semilla de landings a las filas existentes de soluciones y productos.
 * Solo rellena la landing cuando está vacía (no pisa ediciones del admin).
 * Soluciones se emparejan por `skey`; productos por el slug del nombre ES.
 * Usada por install.php y migrate.php. Idempotente.
 */
if (! function_exists('exp_slug_landing')) {
    function exp_slug_landing(string $s): string
    {
        $s = trim($s);
        if (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($t !== false) { $s = $t; }
        }
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return substr(trim($s, '-'), 0, 80);
    }
}

if (! function_exists('exp_nombre_es')) {
    function exp_nombre_es($v): string
    {
        if (is_array($v)) { return (string) ($v['es'] ?? reset($v) ?? ''); }
        $d = is_string($v) ? json_decode($v, true) : null;
        if (is_array($d)) { return (string) ($d['es'] ?? reset($d) ?? ''); }
        return (string) $v;
    }
}

if (! function_exists('exp_apply_landings')) {
    function exp_apply_landings(PDO $pdo): int
    {
        $seed = require __DIR__ . '/seed_landings.php';
        $n = 0;
        $vacio = fn ($v) => $v === null || $v === '' || $v === '[]' || $v === '{}' || $v === 'null';

        // Soluciones por skey.
        try {
            $rows = $pdo->query('SELECT id, skey, landing FROM solutions')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                if (! $vacio($r['landing'] ?? null)) { continue; }
                $land = $seed['solutions'][$r['skey']] ?? null;
                if (! $land) { continue; }
                $pdo->prepare('UPDATE solutions SET landing = ? WHERE id = ?')
                    ->execute([json_encode($land, JSON_UNESCAPED_UNICODE), $r['id']]);
                $n++;
            }
        } catch (\Throwable $e) { /* tabla o columna aún ausente */ }

        // Productos por slug del nombre ES.
        try {
            $rows = $pdo->query('SELECT id, nombre, landing FROM products')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                if (! $vacio($r['landing'] ?? null)) { continue; }
                $slug = exp_slug_landing(exp_nombre_es($r['nombre']));
                $land = $seed['products'][$slug] ?? null;
                if (! $land) { continue; }
                $pdo->prepare('UPDATE products SET landing = ? WHERE id = ?')
                    ->execute([json_encode($land, JSON_UNESCAPED_UNICODE), $r['id']]);
                $n++;
            }
        } catch (\Throwable $e) { /* tabla o columna aún ausente */ }

        return $n;
    }
}
