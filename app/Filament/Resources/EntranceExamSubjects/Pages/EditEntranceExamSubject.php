<?php

namespace App\Filament\Resources\EntranceExamSubjects\Pages;

use App\Filament\Resources\EntranceExamSubjects\EntranceExamSubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntranceExamSubject extends EditRecord
{
    protected static string $resource = EntranceExamSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
