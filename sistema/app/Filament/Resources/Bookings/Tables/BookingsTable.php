<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('experientia.booking.timezone'))
                    ->sortable(),
                TextColumn::make('lead.name')
                    ->label('Lead')
                    ->description(fn (Booking $record) => $record->lead?->company)
                    ->url(fn (Booking $record) => $record->lead
                        ? route('filament.admin.resources.leads.edit', $record->lead)
                        : null),
                TextColumn::make('tema')->label('Tema')->wrap()->limit(120),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Booking::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'confirmada' => 'success',
                        'realizada' => 'info',
                        'cancelada' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(Booking::STATUSES),
            ])
            ->recordActions([EditAction::make()]);
    }
}
