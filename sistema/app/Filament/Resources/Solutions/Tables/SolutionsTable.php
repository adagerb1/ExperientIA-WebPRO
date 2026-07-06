<?php

namespace App\Filament\Resources\Solutions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SolutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Solución')
                    ->formatStateUsing(fn ($state) => tr($state))
                    ->description(fn ($record) => tr($record->pilar)),
                TextColumn::make('key')->label('Clave')->badge()->color('gray'),
                IconColumn::make('active')->label('Activa')->boolean(),
                TextColumn::make('updated_at')->label('Actualizada')->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
