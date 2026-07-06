<?php

namespace App\Filament\Resources\Resources\Schemas;

use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ResourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->helperText('Se genera del título si lo dejas vacío.')
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),
                Select::make('type')
                    ->label('Tipo de recurso')
                    ->options([
                        'article' => 'Artículo (lectura libre)',
                        'download' => 'Descargable (con formulario de datos)',
                    ])
                    ->default('article')
                    ->required()
                    ->live(),
                DateTimePicker::make('published_at')
                    ->label('Publicado desde')
                    ->default(now())
                    ->helperText('Vacío = borrador (no visible en el sitio).'),
                TextInput::make('sort')->label('Orden')->numeric()->default(0),
                Toggle::make('active')->label('Activo')->default(true),
                FileUpload::make('file_path')
                    ->label('Archivo descargable (PDF)')
                    ->disk('local')
                    ->directory('recursos')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(20480)
                    ->visible(fn ($get) => $get('type') === 'download')
                    ->requiredIf('type', 'download')
                    ->columnSpanFull(),
                LocaleTabs::make(fn (string $l, bool $req) => [
                    TextInput::make("tipo_label.{$l}")
                        ->label('Etiqueta de tipo (p. ej. Guía ejecutiva, Playbook)')
                        ->required($req),
                    TextInput::make("titulo.{$l}")
                        ->label('Título')
                        ->required($req)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) use ($l) {
                            if ($l === 'es' && blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Textarea::make("extracto.{$l}")->label('Extracto (tarjeta y SEO)')->rows(3)->required($req),
                    RichEditor::make("cuerpo.{$l}")
                        ->label('Contenido del artículo')
                        ->visible(fn ($get) => $get('type') === 'article'),
                ]),
            ]);
    }
}
