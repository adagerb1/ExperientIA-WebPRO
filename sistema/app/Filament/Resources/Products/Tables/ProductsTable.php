<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('nombre')
                    ->label('Producto')
                    ->formatStateUsing(fn ($state) => tr($state))
                    ->description(fn ($record) => tr($record->rol)),
                IconColumn::make('destacado')->label('Destacado')->boolean(),
                IconColumn::make('active')->label('Activo')->boolean(),
                TextColumn::make('updated_at')->label('Actualizado')->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
