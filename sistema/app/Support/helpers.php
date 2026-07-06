<?php

use Illuminate\Support\Facades\App;

if (! function_exists('tr')) {
    /**
     * Devuelve la traducción de un campo JSON {es,en,pt} en el idioma actual,
     * con respaldo al español cuando la traducción no existe o está vacía.
     */
    function tr(mixed $field, ?string $locale = null): mixed
    {
        if (! is_array($field)) {
            return $field;
        }

        $locale ??= App::getLocale();

        $value = $field[$locale] ?? null;

        if ($value === null || $value === '' || $value === []) {
            $value = $field['es'] ?? reset($field) ?: '';
        }

        return $value;
    }
}

if (! function_exists('tr_lines')) {
    /** Igual que tr(), pero divide el texto en líneas no vacías (para listas). */
    function tr_lines(mixed $field, ?string $locale = null): array
    {
        $value = tr($field, $locale);

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return array_values(array_filter(array_map('trim', explode("\n", (string) $value))));
    }
}
