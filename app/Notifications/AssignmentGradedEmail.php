<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentGradedEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $assignmentTitle,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('MK Scholars assignment graded')
            ->greeting('Hello '.$this->studentName.',')
            ->line('Your submission for '.$this->assignmentTitle.' has been graded.')
            ->line('Open the assignment page to review your score and feedback.')
            ->line('MK Scholars - Learn skills. Get coached. Earn certificates. Apply for opportunities.');

        return $this->actionUrl
            ? $message->action('View assignment', $this->actionUrl)
            : $message;
    }
}
