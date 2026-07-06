<?php

namespace App\Filament\Resources\AvailabilityRules;

use App\Filament\Resources\AvailabilityRules\Pages\CreateAvailabilityRule;
use App\Filament\Resources\AvailabilityRules\Pages\EditAvailabilityRule;
use App\Filament\Resources\AvailabilityRules\Pages\ListAvailabilityRules;
use App\Filament\Resources\AvailabilityRules\Schemas\AvailabilityRuleForm;
use App\Filament\Resources\AvailabilityRules\Tables\AvailabilityRulesTable;
use App\Models\AvailabilityRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AvailabilityRuleResource extends Resource
{
    protected static ?string $model = AvailabilityRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = 'Franja de disponibilidad';

    protected static ?string $pluralModelLabel = 'Disponibilidad';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AvailabilityRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AvailabilityRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAvailabilityRules::route('/'),
            'create' => CreateAvailabilityRule::route('/create'),
            'edit' => EditAvailabilityRule::route('/{record}/edit'),
        ];
    }
}
