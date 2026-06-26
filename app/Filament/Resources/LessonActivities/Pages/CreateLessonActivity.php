<?php

namespace App\Filament\Resources\LessonActivities\Pages;

use App\Filament\Resources\LessonActivities\LessonActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonActivity extends CreateRecord
{
    protected static string $resource = LessonActivityResource::class;
}
