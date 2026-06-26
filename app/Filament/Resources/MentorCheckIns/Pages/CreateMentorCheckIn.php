<?php

namespace App\Filament\Resources\MentorCheckIns\Pages;

use App\Filament\Resources\MentorCheckIns\MentorCheckInResource;
use App\Models\AppNotification;
use App\Services\AppNotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreateMentorCheckIn extends CreateRecord
{
    protected static string $resource = MentorCheckInResource::class;

    protected function afterCreate(): void
    {
        $this->record->load('mentorAssignment.student', 'mentorAssignment.mentor');

        $assignment = $this->record->mentorAssignment;

        if (! $assignment) {
            return;
        }

        $message = 'A mentor check-in has been scheduled'.($this->record->scheduled_at ? ' for '.$this->record->scheduled_at->format('M j, Y g:i A') : '').'.';

        app(AppNotificationService::class)->createForUser($assignment->student_id, [
            'title' => 'Mentor check-in scheduled',
            'message' => $message,
            'type' => AppNotification::TYPE_REMINDER,
            'category' => AppNotification::CATEGORY_MENTORSHIP,
            'action_url' => route('student.mentorship'),
            'created_by' => auth()->id(),
        ]);

        app(AppNotificationService::class)->createForUser($assignment->mentor_id, [
            'title' => 'Mentor check-in scheduled',
            'message' => $message,
            'type' => AppNotification::TYPE_REMINDER,
            'category' => AppNotification::CATEGORY_MENTORSHIP,
            'action_url' => route('mentor.check-ins'),
            'created_by' => auth()->id(),
        ]);
    }
}
