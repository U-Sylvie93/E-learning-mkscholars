<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PaymentApprovedEmail;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFake;
use RuntimeException;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_notification_is_not_sent_when_disabled(): void
    {
        config(['mkscholars.email_notifications.enabled' => false]);
        NotificationFake::fake();

        $student = $this->student();

        $sent = app(EmailNotificationService::class)->sendToUser(
            $student,
            new PaymentApprovedEmail($student->name, 'Demo Course', 'https://example.test/payment'),
        );

        $this->assertFalse($sent);
        NotificationFake::assertNothingSent();
    }

    public function test_email_notification_is_sent_when_enabled(): void
    {
        config(['mkscholars.email_notifications.enabled' => true]);
        NotificationFake::fake();

        $student = $this->student('enabled-email@mkscholars.test');

        $sent = app(EmailNotificationService::class)->sendToUser(
            $student,
            new PaymentApprovedEmail($student->name, 'Demo Course', 'https://example.test/payment'),
        );

        $this->assertTrue($sent);
        NotificationFake::assertSentTo($student, PaymentApprovedEmail::class);
    }

    public function test_null_user_skips_email_safely(): void
    {
        config(['mkscholars.email_notifications.enabled' => true]);
        NotificationFake::fake();

        $sent = app(EmailNotificationService::class)->sendToUser(
            null,
            new PaymentApprovedEmail('Missing Student', 'Demo Course', 'https://example.test/payment'),
        );

        $this->assertFalse($sent);
        NotificationFake::assertNothingSent();
    }

    public function test_blank_user_email_skips_email_safely(): void
    {
        config(['mkscholars.email_notifications.enabled' => true]);
        NotificationFake::fake();

        $student = new User([
            'name' => 'Blank Email Student',
            'email' => '',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $sent = app(EmailNotificationService::class)->sendToUser(
            $student,
            new PaymentApprovedEmail($student->name, 'Demo Course', 'https://example.test/payment'),
        );

        $this->assertFalse($sent);
        NotificationFake::assertNothingSent();
    }

    public function test_email_failure_does_not_break_main_action(): void
    {
        config(['mkscholars.email_notifications.enabled' => true]);

        $student = $this->student('failure-email@mkscholars.test');

        $sent = app(EmailNotificationService::class)->sendToUser(
            $student,
            new class extends Notification {
                public function via(object $notifiable): array
                {
                    throw new RuntimeException('Mail transport failed.');
                }
            },
        );

        $this->assertFalse($sent);
    }

    private function student(string $email = 'email-student@mkscholars.test'): User
    {
        return User::create([
            'name' => 'Email Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
    }
}
