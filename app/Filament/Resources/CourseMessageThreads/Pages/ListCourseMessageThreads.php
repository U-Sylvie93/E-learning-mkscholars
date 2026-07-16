<?php

namespace App\Filament\Resources\CourseMessageThreads\Pages;

use App\Filament\Resources\CourseMessageThreads\CourseMessageThreadResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseMessageThreads extends ListRecords
{
    protected static string $resource = CourseMessageThreadResource::class;
}
