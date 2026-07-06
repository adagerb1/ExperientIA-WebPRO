<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Symfony\Component\Intl\Countries;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contacto')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')->label('Nombre')->required(),
                        TextInput::make('email')->label('Correo')->email(),
                        TextInput::make('phone_wa')
                            ->label('WhatsApp (E.164 sin +)')
                            ->helperText('Ej.: 573001234567 — se usa para wa.me')
                            ->tel(),
                        Select::make('country')
                            ->label('País')
                            ->options(collect(Countries::getNames('es'))->sortBy(fn ($v) => $v)->all())
                            ->searchable(),
                    ]),
                Section::make('Empresa')
                    ->columns(2)
                    ->components([
                        TextInput::make('company')->label('Empresa'),
                        TextInput::make('role')->label('Cargo'),
                        Select::make('industry')
                            ->label('Industria')
                            ->options(config('experientia.industries'))
                            ->searchable(),
                        Select::make('company_size')
                            ->label('Tamaño (empleados)')
                            ->options(Lead::SIZES),
                    ]),
                Section::make('Gestión')
                    ->columns(2)
                    ->components([
                        Select::make('status')
                            ->label('Estado')
                            ->options(Lead::STATUSES)
                            ->default('nuevo')
                            ->required(),
                        Select::make('locale')
                            ->label('Idioma')
                            ->options(['es' => 'Español', 'en' => 'English', 'pt' => 'Português'])
                            ->default('es'),
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
