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

if (! function_exists('lroute')) {
    /** Ruta nombrada en el idioma actual (o el indicado): lroute('home') → es.home */
    function lroute(string $name, array $params = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return route("{$locale}.{$name}", $params);
    }
}

if (! function_exists('locale_switch_url')) {
    /** URL de la página actual en otro idioma (para el selector ES/EN/PT). */
    function locale_switch_url(string $locale): string
    {
        $route = request()->route();

        if (! $route || ! $route->getName() || ! str_contains($route->getName(), '.')) {
            return url("/{$locale}");
        }

        [, $name] = explode('.', $route->getName(), 2);

        try {
            return route("{$locale}.{$name}", $route->parameters());
        } catch (\Throwable) {
            return url("/{$locale}");
        }
    }
}
