<?php

namespace App\Filament\Resources\EntranceExamPastPapers\Pages;

use App\Filament\Resources\EntranceExamPastPapers\EntranceExamPastPaperResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntranceExamPastPaper extends EditRecord
{
    protected static string $resource = EntranceExamPastPaperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
