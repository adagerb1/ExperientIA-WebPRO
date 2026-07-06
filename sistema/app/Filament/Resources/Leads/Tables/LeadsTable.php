<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Lead;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->description(fn (Lead $record) => $record->company)
                    ->searchable(['name', 'company']),
                TextColumn::make('email')
                    ->label('Correo')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('phone_wa')
                    ->label('WhatsApp')
                    ->formatStateUsing(fn (?string $state) => $state ? "+{$state}" : '—')
                    ->url(fn (Lead $record) => $record->whatsappUrl(), shouldOpenInNewTab: true)
                    ->searchable(),
                TextColumn::make('industry')
                    ->label('Industria')
                    ->formatStateUsing(fn (?string $state) => config("experientia.industries.{$state}", $state))
                    ->toggleable(),
                TextColumn::make('company_size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn (?string $state) => Lead::SIZES[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Lead::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'nuevo' => 'info',
                        'contactado' => 'warning',
                        'calificado' => 'primary',
                        'propuesta' => 'warning',
                        'cliente' => 'success',
                        'descartado' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('touchpoints_count')
                    ->label('Interacciones')
                    ->counts('touchpoints')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Lead::STATUSES),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options([
                        'contacto' => 'Formulario de contacto',
                        'descarga' => 'Descarga de recurso',
                        'diagnostico' => 'Diagnóstico',
                        'reserva' => 'Reserva de sesión',
                        'newsletter' => 'Newsletter',
                    ]),
                SelectFilter::make('industry')
                    ->label('Industria')
                    ->options(config('experientia.industries')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
