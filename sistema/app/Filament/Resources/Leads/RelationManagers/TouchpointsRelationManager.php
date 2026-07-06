<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Models\Touchpoint;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TouchpointsRelationManager extends RelationManager
{
    protected static string $relationship = 'touchpoints';

    protected static ?string $title = 'Historial de interacciones';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Touchpoint::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'contacto' => 'info',
                        'descarga' => 'primary',
                        'diagnostico' => 'warning',
                        'reserva' => 'success',
                        'newsletter' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('title')
                    ->label('Interacción')
                    ->wrap(),
                TextColumn::make('payload')
                    ->label('Detalle')
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return '—';
                        }
                        $data = is_array($state) ? $state : json_decode($state, true);
                        return collect($data)
                            ->map(fn ($v, $k) => is_scalar($v) ? "{$k}: {$v}" : "{$k}: " . json_encode($v, JSON_UNESCAPED_UNICODE))
                            ->take(6)
                            ->implode(' · ');
                    })
                    ->wrap()
                    ->limit(220),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
