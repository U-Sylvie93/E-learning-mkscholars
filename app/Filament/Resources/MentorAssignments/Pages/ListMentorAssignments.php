<?php

namespace App\Filament\Resources\MentorAssignments\Pages;

use App\Filament\Resources\MentorAssignments\MentorAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMentorAssignments extends ListRecords
{
    protected static string $resource = MentorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
