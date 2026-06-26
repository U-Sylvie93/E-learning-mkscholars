<?php

namespace App\Filament\Resources\LessonActivities\Pages;

use App\Filament\Resources\LessonActivities\LessonActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonActivity extends EditRecord
{
    protected static string $resource = LessonActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
