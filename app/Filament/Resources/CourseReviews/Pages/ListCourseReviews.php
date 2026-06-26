<?php

namespace App\Filament\Resources\CourseReviews\Pages;

use App\Filament\Resources\CourseReviews\CourseReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseReviews extends ListRecords
{
    protected static string $resource = CourseReviewResource::class;
}
