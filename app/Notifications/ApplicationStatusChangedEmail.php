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
        public string $opportunityTitle,
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
            ->line('Your application for '.$this->opportunityTitle.' is now '.$this->status.'.')
            ->line('Open your application tracker to review the latest status and next steps.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('View application', $this->actionUrl)
            : $message;
    }
}
