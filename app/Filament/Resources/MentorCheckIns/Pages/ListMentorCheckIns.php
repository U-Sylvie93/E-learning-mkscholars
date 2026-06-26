<?php

namespace App\Filament\Resources\MentorCheckIns\Pages;

use App\Filament\Resources\MentorCheckIns\MentorCheckInResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMentorCheckIns extends ListRecords
{
    protected static string $resource = MentorCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
