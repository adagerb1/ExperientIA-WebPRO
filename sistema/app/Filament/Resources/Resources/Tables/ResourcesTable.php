<?php

namespace App\Filament\Resources\Resources\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ResourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Recurso')
                    ->formatStateUsing(fn ($state) => tr($state))
                    ->description(fn ($record) => tr($record->tipo_label)),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'download' ? 'Descargable' : 'Artículo')
                    ->color(fn (string $state) => $state === 'download' ? 'primary' : 'info'),
                TextColumn::make('downloads')->label('Descargas')->badge()->color('gray'),
                IconColumn::make('active')->label('Activo')->boolean(),
                TextColumn::make('published_at')->label('Publicado')->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options([
                    'article' => 'Artículo',
                    'download' => 'Descargable',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
