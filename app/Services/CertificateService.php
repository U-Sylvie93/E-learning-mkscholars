<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Notifications\CertificateIssuedEmail;
use Illuminate\Support\Facades\DB;

class CertificateService
{
    public function prepareForEligibleCompletion(User $student, Course $course): Certificate
    {
        abort_unless($course->offersCertificate(), 422, 'This course does not offer certificates.');

        return DB::transaction(function () use ($student, $course): Certificate {
            $existing = Certificate::query()
                ->where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->oldest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            return Certificate::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'student_name' => $student->name,
                'course_title' => $course->title,
                'score' => Certificate::finalTestScoreFor($student, $course),
                'status' => Certificate::STATUS_PENDING,
                'issued_at' => now(),
            ]);
        });
    }

    public function issue(Certificate $certificate, User $admin): Certificate
    {
        $this->authorizeAdmin($admin);

        abort_unless(in_array($certificate->status, [Certificate::STATUS_PENDING, Certificate::STATUS_REJECTED], true), 422);

        $certificate->update([
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
            'revoked_at' => null,
        ]);

        $this->notifyIssued($certificate, $admin);

        return $certificate->refresh();
    }

    public function reject(Certificate $certificate, User $admin, ?string $reason = null): Certificate
    {
        $this->authorizeAdmin($admin);
        abort_unless($certificate->status === Certificate::STATUS_PENDING, 422);

        $certificate->update([
            'status' => Certificate::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => filled($reason) ? trim($reason) : null,
            'revoked_at' => null,
        ]);

        app(AppNotificationService::class)->createForUser($certificate->user_id, [
            'title' => 'Certificate request reviewed',
            'message' => 'Your certificate request for '.$certificate->course_title.' was not approved.'.(filled($reason) ? ' Reason: '.trim($reason) : ''),
            'type' => AppNotification::TYPE_WARNING,
            'category' => AppNotification::CATEGORY_CERTIFICATE,
            'action_url' => route('student.certificates.show', $certificate),
            'created_by' => $admin->id,
        ]);

        return $certificate->refresh();
    }

    public function revoke(Certificate $certificate, User $admin): Certificate
    {
        $this->authorizeAdmin($admin);
        abort_unless($certificate->status === Certificate::STATUS_ISSUED, 422);

        $certificate->update([
            'status' => Certificate::STATUS_REVOKED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'revoked_at' => now(),
        ]);

        return $certificate->refresh();
    }

    private function authorizeAdmin(User $user): void
    {
        abort_unless($user->role === User::ROLE_ADMIN, 403);
    }

    private function notifyIssued(Certificate $certificate, User $admin): void
    {
        app(AppNotificationService::class)->createForUser($certificate->user_id, [
            'title' => 'Certificate issued',
            'message' => 'Your certificate for '.$certificate->course_title.' has been issued.',
            'type' => AppNotification::TYPE_SUCCESS,
            'category' => AppNotification::CATEGORY_CERTIFICATE,
            'action_url' => route('student.certificates.show', $certificate),
            'created_by' => $admin->id,
        ]);

        if ($certificate->user) {
            app(EmailNotificationService::class)->sendToUser(
                $certificate->user,
                new CertificateIssuedEmail(
                    $certificate->user->name,
                    $certificate->course_title,
                    route('student.certificates.show', $certificate),
                ),
            );
        }

        if ($certificate->course?->instructor_id) {
            app(AppNotificationService::class)->createForUser($certificate->course->instructor_id, [
                'title' => 'Student certificate issued',
                'message' => $certificate->student_name.' received a certificate for '.$certificate->course_title.'.',
                'type' => AppNotification::TYPE_SUCCESS,
                'category' => AppNotification::CATEGORY_CERTIFICATE,
                'created_by' => $admin->id,
            ]);
        }
    }
}
