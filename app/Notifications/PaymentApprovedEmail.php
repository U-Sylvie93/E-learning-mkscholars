<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $paymentFor,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('MK Scholars payment approved')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your payment for '.$this->paymentFor.' has been approved.')
            ->line('Your access has been updated in the MK Scholars platform.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('View payment', $this->actionUrl)
            : $message;
    }
}
