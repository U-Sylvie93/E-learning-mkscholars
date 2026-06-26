<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssuedEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $courseTitle,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('MK Scholars certificate issued')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your certificate for '.$this->courseTitle.' has been issued.')
            ->line('You can view, print, and share the public verification link from your certificate page.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('View certificate', $this->actionUrl)
            : $message;
    }
}
