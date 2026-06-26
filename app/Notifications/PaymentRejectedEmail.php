<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedEmail extends Notification
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
            ->subject('MK Scholars payment needs attention')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your payment for '.$this->paymentFor.' could not be approved yet.')
            ->line('Please review the payment page and upload updated proof if needed.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('Review payment', $this->actionUrl)
            : $message;
    }
}
