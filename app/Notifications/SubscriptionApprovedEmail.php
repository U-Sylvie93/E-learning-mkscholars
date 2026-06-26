<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionApprovedEmail extends Notification
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
            ->subject('MK Scholars subscription approved')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your '.$this->planName.' subscription has been approved.')
            ->line('You can now access the courses included in your active plan.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('View subscription', $this->actionUrl)
            : $message;
    }
}
