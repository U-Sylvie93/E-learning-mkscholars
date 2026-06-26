<?php

namespace App\Filament\Resources\MentorCheckIns\Pages;

use App\Filament\Resources\MentorCheckIns\MentorCheckInResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMentorCheckIn extends EditRecord
{
    protected static string $resource = MentorCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
