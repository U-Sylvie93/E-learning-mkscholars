<?php

namespace App\Filament\Resources\LiveClasses\Pages;

use App\Filament\Resources\LiveClasses\LiveClassResource;
use App\Models\AppNotification;
use App\Models\Enrollment;
use App\Services\AppNotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreateLiveClass extends CreateRecord
{
    protected static string $resource = LiveClassResource::class;

    protected function afterCreate(): void
    {
        $this->record->load('course', 'module.course', 'lesson.module.course');
        $course = $this->record->associatedCourse();

        if (! $course) {
            return;
        }

        Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->with('user')
            ->chunkById(100, function ($enrollments): void {
                foreach ($enrollments as $enrollment) {
                    app(AppNotificationService::class)->createForUser($enrollment->user_id, [
                        'title' => 'New live class scheduled',
                        'message' => $this->record->title.' is scheduled for '.$this->record->starts_at->format('M j, Y g:i A').'.',
                        'type' => AppNotification::TYPE_REMINDER,
                        'category' => AppNotification::CATEGORY_LIVE_CLASS,
                        'action_url' => route('student.live-classes'),
                        'created_by' => auth()->id(),
                    ]);
                }
            });
    }
}
