<?php

namespace App\Filament\Resources\EntranceExamInstitutions\Pages;

use App\Filament\Resources\EntranceExamInstitutions\EntranceExamInstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntranceExamInstitution extends EditRecord
{
    protected static string $resource = EntranceExamInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
