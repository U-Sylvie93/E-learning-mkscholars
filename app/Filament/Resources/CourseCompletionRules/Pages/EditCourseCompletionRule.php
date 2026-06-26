<?php

namespace App\Filament\Resources\CourseCompletionRules\Pages;

use App\Filament\Resources\CourseCompletionRules\CourseCompletionRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseCompletionRule extends EditRecord
{
    protected static string $resource = CourseCompletionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
