<?php

namespace App\Filament\Resources\CaseStudies\Schemas;

use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaseStudyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort')->label('Orden')->numeric()->default(0),
                Toggle::make('active')->label('Activo')->default(true),
                LocaleTabs::make(fn (string $l, bool $req) => [
                    TextInput::make("sector.{$l}")->label('Sector (p. ej. "Retail regional · 40+ tiendas")')->required($req),
                    TextInput::make("titulo.{$l}")->label('Título')->required($req),
                    Textarea::make("contexto.{$l}")->label('Contexto')->rows(3)->required($req),
                    Textarea::make("intervencion.{$l}")->label('Intervención')->rows(3)->required($req),
                ]),
                Repeater::make('resultados')
                    ->label('Resultados (métricas)')
                    ->schema([
                        TextInput::make('valor')->label('Valor (p. ej. +31%)')->required(),
                        TextInput::make('label.es')->label('Etiqueta ES')->required(),
                        TextInput::make('label.en')->label('Etiqueta EN'),
                        TextInput::make('label.pt')->label('Etiqueta PT'),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->maxItems(4)
                    ->columnSpanFull(),
            ]);
    }
}
