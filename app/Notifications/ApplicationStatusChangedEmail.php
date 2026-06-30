<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $applicationTitle,
        public string $status,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('MK Scholars application status updated')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your learning request for '.$this->applicationTitle.' is now '.$this->status.'.')
            ->line('Review the latest status and next steps inside your MK Scholars workspace.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Track your progress.');

        return $this->actionUrl
            ? $message->action('Open workspace', $this->actionUrl)
            : $message;
    }
}
