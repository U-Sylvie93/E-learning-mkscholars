<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\AppNotification;
use App\Services\AppNotificationService;
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
            'title' => 'Certificate pending approval',
            'message' => 'Your certificate for '.$this->record->course_title.' is awaiting admin approval.',
            'type' => AppNotification::TYPE_INFO,
            'category' => AppNotification::CATEGORY_CERTIFICATE,
            'action_url' => route('student.certificates.show', $this->record),
            'created_by' => auth()->id(),
        ]);

    }
}
