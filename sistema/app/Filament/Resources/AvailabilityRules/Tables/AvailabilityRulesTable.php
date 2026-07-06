<?php

namespace App\Filament\Resources\AvailabilityRules\Tables;

use App\Models\AvailabilityRule;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AvailabilityRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->columns([
                TextColumn::make('weekday')
                    ->label('Día')
                    ->formatStateUsing(fn (int $state) => AvailabilityRule::WEEKDAYS[$state] ?? $state),
                TextColumn::make('start_time')->label('Desde')->time('H:i'),
                TextColumn::make('end_time')->label('Hasta')->time('H:i'),
                IconColumn::make('active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
