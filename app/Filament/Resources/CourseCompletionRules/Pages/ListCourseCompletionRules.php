<?php

namespace App\Filament\Resources\CourseCompletionRules\Pages;

use App\Filament\Resources\CourseCompletionRules\CourseCompletionRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourseCompletionRules extends ListRecords
{
    protected static string $resource = CourseCompletionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
