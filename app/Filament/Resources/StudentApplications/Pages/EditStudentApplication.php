<?php

namespace App\Filament\Resources\StudentApplications\Pages;

use App\Filament\Resources\StudentApplications\StudentApplicationResource;
use App\Models\AppNotification;
use App\Models\StudentApplication;
use App\Notifications\ApplicationStatusChangedEmail;
use App\Services\AppNotificationService;
use App\Services\EmailNotificationService;
use Filament\Resources\Pages\EditRecord;

class EditStudentApplication extends EditRecord
{
    protected static string $resource = StudentApplicationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? null, [
            StudentApplication::STATUS_UNDER_REVIEW,
            StudentApplication::STATUS_APPROVED,
            StudentApplication::STATUS_REJECTED,
        ], true)) {
            $data['reviewed_at'] ??= now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('status')) {
            return;
        }

        app(AppNotificationService::class)->createForUser($this->record->user_id, [
            'title' => 'Application status updated',
            'message' => 'Your application for '.($this->record->opportunity?->title ?? 'an opportunity').' is now '.str_replace('_', ' ', $this->record->status).'.',
            'type' => in_array($this->record->status, [StudentApplication::STATUS_APPROVED, StudentApplication::STATUS_REJECTED], true)
                ? AppNotification::TYPE_REMINDER
                : AppNotification::TYPE_INFO,
            'category' => AppNotification::CATEGORY_APPLICATION,
            'action_url' => route('student.applications.show', $this->record),
            'created_by' => auth()->id(),
        ]);

        if ($this->record->user) {
            app(EmailNotificationService::class)->sendToUser(
                $this->record->user,
                new ApplicationStatusChangedEmail(
                    $this->record->user->name,
                    $this->record->opportunity?->title ?? 'an opportunity',
                    str_replace('_', ' ', $this->record->status),
                    route('student.applications.show', $this->record),
                ),
            );
        }
    }
}
