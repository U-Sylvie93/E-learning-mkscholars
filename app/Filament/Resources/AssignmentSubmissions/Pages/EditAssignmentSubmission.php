<?php

namespace App\Filament\Resources\AssignmentSubmissions\Pages;

use App\Filament\Resources\AssignmentSubmissions\AssignmentSubmissionResource;
use App\Models\AppNotification;
use App\Models\AssignmentSubmission;
use App\Notifications\AssignmentGradedEmail;
use App\Services\AppNotificationService;
use App\Services\EmailNotificationService;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentSubmission extends EditRecord
{
    protected static string $resource = AssignmentSubmissionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === AssignmentSubmission::STATUS_GRADED) {
            $data['graded_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('status') || $this->record->status !== AssignmentSubmission::STATUS_GRADED) {
            return;
        }

        app(AppNotificationService::class)->createForUser($this->record->user_id, [
            'title' => 'Assignment graded',
            'message' => 'Your submission for '.($this->record->assignment?->title ?? 'an assignment').' has been graded.',
            'type' => AppNotification::TYPE_SUCCESS,
            'category' => AppNotification::CATEGORY_ASSIGNMENT,
            'action_url' => $this->record->assignment ? route('student.assignments.show', $this->record->assignment) : null,
            'created_by' => auth()->id(),
        ]);

        if ($this->record->user) {
            app(EmailNotificationService::class)->sendToUser(
                $this->record->user,
                new AssignmentGradedEmail(
                    $this->record->user->name,
                    $this->record->assignment?->title ?? 'your assignment',
                    $this->record->assignment ? route('student.assignments.show', $this->record->assignment) : null,
                ),
            );
        }
    }
}
