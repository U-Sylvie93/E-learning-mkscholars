<?php

namespace App\Filament\Resources\OpportunityRequirements\Pages;

use App\Filament\Resources\OpportunityRequirements\OpportunityRequirementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpportunityRequirements extends ListRecords
{
    protected static string $resource = OpportunityRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
