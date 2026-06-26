<?php

namespace App\Filament\Resources\LessonActivities\Pages;

use App\Filament\Resources\LessonActivities\LessonActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonActivities extends ListRecords
{
    protected static string $resource = LessonActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
