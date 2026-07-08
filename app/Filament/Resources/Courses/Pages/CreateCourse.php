<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->role === User::ROLE_CONTENT_EDITOR) {
            $data['status'] = Course::STATUS_DRAFT;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if ($user?->role === User::ROLE_CONTENT_EDITOR) {
            $user->assignContentCourse($this->record->id);
        }
    }
}
