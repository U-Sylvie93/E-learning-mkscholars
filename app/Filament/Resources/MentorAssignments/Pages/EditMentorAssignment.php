<?php

namespace App\Filament\Resources\MentorAssignments\Pages;

use App\Filament\Resources\MentorAssignments\MentorAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMentorAssignment extends EditRecord
{
    protected static string $resource = MentorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
