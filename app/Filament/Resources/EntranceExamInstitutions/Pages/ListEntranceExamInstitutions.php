<?php

namespace App\Filament\Resources\EntranceExamInstitutions\Pages;

use App\Filament\Resources\EntranceExamInstitutions\EntranceExamInstitutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntranceExamInstitutions extends ListRecords
{
    protected static string $resource = EntranceExamInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
