<?php

namespace App\Filament\Resources\EntranceExamPastPapers\Pages;

use App\Filament\Resources\EntranceExamPastPapers\EntranceExamPastPaperResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntranceExamPastPapers extends ListRecords
{
    protected static string $resource = EntranceExamPastPaperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
