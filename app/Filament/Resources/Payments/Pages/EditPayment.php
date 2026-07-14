<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\AppNotification;
use App\Models\Payment;
use App\Notifications\PaymentApprovedEmail;
use App\Notifications\PaymentRejectedEmail;
use App\Notifications\SubscriptionApprovedEmail;
use App\Notifications\SubscriptionRejectedEmail;
use App\Services\AppNotificationService;
use App\Services\EmailNotificationService;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? null, [Payment::STATUS_APPROVED, Payment::STATUS_REJECTED], true)) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] ??= auth()->id();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('status') || ! in_array($this->record->status, [Payment::STATUS_APPROVED, Payment::STATUS_REJECTED], true)) {
            return;
        }

        $label = $this->record->payableTitle();
        $isSubscription = $this->record->purpose === Payment::PURPOSE_SUBSCRIPTION;

        app(AppNotificationService::class)->createForUser($this->record->user_id, [
            'title' => match (true) {
                $isSubscription && $this->record->status === Payment::STATUS_APPROVED => 'Subscription approved',
                $isSubscription && $this->record->status === Payment::STATUS_REJECTED => 'Subscription payment rejected',
                $this->record->status === Payment::STATUS_APPROVED => 'Payment approved',
                default => 'Payment rejected',
            },
            'message' => $this->record->status === Payment::STATUS_APPROVED
                ? 'Your payment for '.$label.' was approved.'
                : 'Your payment for '.$label.' needs attention.',
            'type' => $this->record->status === Payment::STATUS_APPROVED ? AppNotification::TYPE_SUCCESS : AppNotification::TYPE_WARNING,
            'category' => AppNotification::CATEGORY_PAYMENT,
            'action_url' => route('student.payments.show', $this->record),
            'created_by' => auth()->id(),
        ]);

        $student = $this->record->user;
        $actionUrl = route('student.payments.show', $this->record);

        if ($student) {
            $notification = match (true) {
                $isSubscription && $this->record->status === Payment::STATUS_APPROVED => new SubscriptionApprovedEmail($student->name, $label, $actionUrl),
                $isSubscription && $this->record->status === Payment::STATUS_REJECTED => new SubscriptionRejectedEmail($student->name, $label, $actionUrl),
                $this->record->status === Payment::STATUS_APPROVED => new PaymentApprovedEmail($student->name, $label, $actionUrl),
                default => new PaymentRejectedEmail($student->name, $label, $actionUrl),
            };

            app(EmailNotificationService::class)->sendToUser($student, $notification);
        }
    }
}
