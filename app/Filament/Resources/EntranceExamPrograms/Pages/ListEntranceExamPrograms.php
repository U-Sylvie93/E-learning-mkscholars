<?php

namespace App\Filament\Resources\EntranceExamPrograms\Pages;

use App\Filament\Resources\EntranceExamPrograms\EntranceExamProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntranceExamPrograms extends ListRecords
{
    protected static string $resource = EntranceExamProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
