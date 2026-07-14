<?php

namespace App\Filament\Resources\EntranceExamPrograms\Pages;

use App\Filament\Resources\EntranceExamPrograms\EntranceExamProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntranceExamProgram extends EditRecord
{
    protected static string $resource = EntranceExamProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
