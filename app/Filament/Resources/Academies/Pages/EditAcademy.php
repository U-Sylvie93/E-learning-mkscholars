<?php

namespace App\Filament\Resources\Academies\Pages;

use App\Filament\Resources\Academies\AcademyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademy extends EditRecord
{
    protected static string $resource = AcademyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
