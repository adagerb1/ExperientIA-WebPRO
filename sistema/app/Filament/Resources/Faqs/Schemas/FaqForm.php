<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort')->label('Orden')->numeric()->default(0),
                Toggle::make('active')->label('Activa')->default(true),
                LocaleTabs::make(fn (string $l, bool $req) => [
                    TextInput::make("pregunta.{$l}")->label('Pregunta')->required($req),
                    Textarea::make("respuesta.{$l}")->label('Respuesta')->rows(5)->required($req),
                ]),
            ]);
    }
}
