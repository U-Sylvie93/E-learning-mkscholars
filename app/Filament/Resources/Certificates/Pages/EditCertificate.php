<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\AppNotification;
use App\Models\Certificate;
use App\Notifications\CertificateIssuedEmail;
use App\Services\AppNotificationService;
use App\Services\EmailNotificationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CertificateResource::normalizeCertificateData($data, $this->record);
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('status') || $this->record->status !== Certificate::STATUS_ISSUED) {
            return;
        }

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
