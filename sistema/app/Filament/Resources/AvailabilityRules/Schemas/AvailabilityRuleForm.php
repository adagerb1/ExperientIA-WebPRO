<?php

namespace App\Filament\Resources\AvailabilityRules\Schemas;

use App\Models\AvailabilityRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AvailabilityRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('weekday')
                    ->label('Día de la semana')
                    ->options(AvailabilityRule::WEEKDAYS)
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Desde')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Hasta')
                    ->seconds(false)
                    ->required()
                    ->after('start_time'),
                Toggle::make('active')->label('Activa')->default(true),
            ]);
    }
}
