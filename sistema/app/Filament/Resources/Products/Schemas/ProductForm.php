<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('icon')
                    ->label('Ícono')
                    ->options([
                        'analitica' => 'Panel / Tablero',
                        'target' => 'Diana / Diagnóstico',
                        'gear' => 'Engranaje / Automatización',
                        'people' => 'Personas / Academia',
                        'cube' => 'Cubo / Producto',
                        'growth' => 'Crecimiento',
                    ])
                    ->default('cube')
                    ->required(),
                Toggle::make('destacado')
                    ->label('Destacado')
                    ->helperText('Resalta la tarjeta con marco luminoso.'),
                TextInput::make('sort')->label('Orden')->numeric()->default(0),
                Toggle::make('active')->label('Activo')->default(true),
                LocaleTabs::make(fn (string $l, bool $req) => [
                    TextInput::make("nombre.{$l}")->label('Nombre')->required($req),
                    TextInput::make("rol.{$l}")->label('Rol (etiqueta corta, p. ej. "El activo central")')->required($req),
                    Textarea::make("texto.{$l}")->label('Descripción')->rows(3)->required($req),
                ]),
            ]);
    }
}
