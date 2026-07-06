<?php

namespace App\Filament\Resources\CaseStudies\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaseStudiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Caso')
                    ->formatStateUsing(fn ($state) => tr($state))
                    ->description(fn ($record) => tr($record->sector)),
                IconColumn::make('active')->label('Activo')->boolean(),
                TextColumn::make('updated_at')->label('Actualizado')->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
