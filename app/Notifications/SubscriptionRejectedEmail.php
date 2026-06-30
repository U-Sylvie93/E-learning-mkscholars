<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRejectedEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $planName,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('MK Scholars subscription payment needs attention')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your payment for the '.$this->planName.' subscription could not be approved yet.')
            ->line('Please review your payment proof and resubmit when ready.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Track your progress.');

        return $this->actionUrl
            ? $message->action('Review subscription payment', $this->actionUrl)
            : $message;
    }
}
