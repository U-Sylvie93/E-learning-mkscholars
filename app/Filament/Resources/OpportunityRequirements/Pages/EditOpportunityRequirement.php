<?php

namespace App\Filament\Resources\OpportunityRequirements\Pages;

use App\Filament\Resources\OpportunityRequirements\OpportunityRequirementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpportunityRequirement extends EditRecord
{
    protected static string $resource = OpportunityRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
