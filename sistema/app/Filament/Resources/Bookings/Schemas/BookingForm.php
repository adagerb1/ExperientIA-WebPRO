<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lead_id')
                    ->label('Lead')
                    ->relationship('lead', 'name')
                    ->searchable()
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Inicio')
                    ->timezone(config('experientia.booking.timezone'))
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->timezone(config('experientia.booking.timezone'))
                    ->seconds(false)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(Booking::STATUSES)
                    ->default('confirmada')
                    ->required(),
                Textarea::make('tema')->label('Tema')->rows(3)->columnSpanFull(),
            ]);
    }
}
