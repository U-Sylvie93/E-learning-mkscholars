<?php

namespace App\Filament\Resources\Academies\Pages;

use App\Filament\Resources\Academies\AcademyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademies extends ListRecords
{
    protected static string $resource = AcademyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
