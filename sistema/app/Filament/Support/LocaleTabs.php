<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Pestañas ES/EN/PT para campos traducibles. Solo el español es obligatorio:
 * si una traducción queda vacía, el sitio público muestra el español (fallback).
 */
class LocaleTabs
{
    public const LOCALES = [
        'es' => 'Español',
        'en' => 'English',
        'pt' => 'Português',
    ];

    /**
     * @param callable(string $locale, bool $required): array $fields
     */
    public static function make(callable $fields): Tabs
    {
        return Tabs::make('Traducciones')
            ->tabs(collect(self::LOCALES)->map(function (string $label, string $locale) use ($fields) {
                return Tab::make($label)
                    ->schema($fields($locale, $locale === 'es'));
            })->values()->all())
            ->columnSpanFull();
    }
}
