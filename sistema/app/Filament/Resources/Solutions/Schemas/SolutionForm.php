<?php

namespace App\Filament\Resources\Solutions\Schemas;

use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SolutionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Clave interna')
                    ->helperText('Identificador estable (p. ej. estrategia, automatizacion). No cambiarlo después de crear.')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Select::make('icon')
                    ->label('Ícono')
                    ->options([
                        'target' => 'Estrategia (diana)',
                        'gear' => 'Automatización (engranaje)',
                        'analitica' => 'Analítica (panel)',
                        'growth' => 'Crecimiento (gráfica)',
                        'ia' => 'IA (nodos)',
                        'bulb' => 'Innovación (bombillo)',
                        'cube' => 'Soluciones (cubo)',
                        'people' => 'Personas',
                        'shield' => 'Confianza (escudo)',
                        'impulso' => 'Impulso (cohete)',
                    ])
                    ->default('target')
                    ->required(),
                TextInput::make('sort')->label('Orden')->numeric()->default(0),
                Toggle::make('active')->label('Activa')->default(true),
                LocaleTabs::make(fn (string $l, bool $req) => [
                    TextInput::make("titulo.{$l}")->label('Título')->required($req),
                    TextInput::make("pilar.{$l}")->label('Pilar (etiqueta corta)')->required($req),
                    Textarea::make("problema.{$l}")->label('El problema')->rows(3)->required($req),
                    Textarea::make("como.{$l}")
                        ->label('Cómo lo hacemos (un punto por línea)')
                        ->rows(4)
                        ->required($req),
                    Textarea::make("cambia.{$l}")->label('Qué cambia en el negocio')->rows(3)->required($req),
                ]),
            ]);
    }
}
