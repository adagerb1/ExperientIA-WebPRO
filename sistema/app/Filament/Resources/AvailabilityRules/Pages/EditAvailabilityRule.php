<?php

namespace App\Filament\Resources\AvailabilityRules\Pages;

use App\Filament\Resources\AvailabilityRules\AvailabilityRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityRule extends EditRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
