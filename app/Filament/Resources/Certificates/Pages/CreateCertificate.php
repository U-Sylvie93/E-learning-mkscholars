<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\AppNotification;
use App\Notifications\CertificateIssuedEmail;
use App\Services\AppNotificationService;
use App\Services\EmailNotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificate extends CreateRecord
{
    protected static string $resource = CertificateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return CertificateResource::normalizeCertificateData($data);
    }

    protected function afterCreate(): void
    {
        app(AppNotificationService::class)->createForUser($this->record->user_id, [
            'title' => 'Certificate issued',
            'message' => 'Your certificate for '.$this->record->course_title.' has been issued.',
            'type' => AppNotification::TYPE_SUCCESS,
            'category' => AppNotification::CATEGORY_CERTIFICATE,
            'action_url' => route('student.certificates.show', $this->record),
            'created_by' => auth()->id(),
        ]);

        if ($this->record->user) {
            app(EmailNotificationService::class)->sendToUser(
                $this->record->user,
                new CertificateIssuedEmail(
                    $this->record->user->name,
                    $this->record->course_title,
                    route('student.certificates.show', $this->record),
                ),
            );
        }
    }
}
