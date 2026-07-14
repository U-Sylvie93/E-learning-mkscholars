<?php

namespace App\Filament\Resources\EntranceExamSubjects\Pages;

use App\Filament\Resources\EntranceExamSubjects\EntranceExamSubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntranceExamSubjects extends ListRecords
{
    protected static string $resource = EntranceExamSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
