<?php
/** Funciones auxiliares globales del backend. */

/** Traducción de un campo JSON {es,en,pt} con respaldo al español. */
function tr(mixed $field, string $locale = 'es'): mixed
{
    if (is_string($field)) {
        $d = json_decode($field, true);
        if (is_array($d)) $field = $d;
    }
    if (! is_array($field)) return (string) ($field ?? '');
    $v = $field[$locale] ?? null;
    if ($v === null || $v === '' || $v === []) {
        $v = $field['es'] ?? (reset($field) ?: '');
    }
    return $v;
}

function now_utc(): string { return gmdate('Y-m-d H:i:s'); }

/** Config estática de negocio (industrias, tamaños, estados…). */
function biz(string $key): mixed
{
    static $c = null;
    $c ??= require dirname(__DIR__) . '/app/config/business.php';
    return $c[$key] ?? null;
}
