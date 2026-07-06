<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('pregunta')
                    ->label('Pregunta')
                    ->formatStateUsing(fn ($state) => tr($state))
                    ->wrap(),
                IconColumn::make('active')->label('Activa')->boolean(),
                TextColumn::make('updated_at')->label('Actualizada')->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
