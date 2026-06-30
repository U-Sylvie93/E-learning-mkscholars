<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\Subscription;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCsvExportService
{
    public function exportLinks(): array
    {
        return [
            $this->link('Students', 'admin.reports.exports.students', 'Learner roster and activity counts.'),
            $this->link('Enrollments', 'admin.reports.exports.enrollments', 'Course enrollment status and progress.'),
            $this->link('Payments', 'admin.reports.exports.payments', 'Manual payment review summary.'),
            $this->link('Subscriptions', 'admin.reports.exports.subscriptions', 'Plan status and subscription windows.'),
            $this->link('Certificates', 'admin.reports.exports.certificates', 'Issued credentials and verification links.'),
            $this->link('Quiz Attempts', 'admin.reports.exports.quiz-attempts', 'Quiz scores and pass/fail outcomes.'),
            $this->link('Assignment Submissions', 'admin.reports.exports.assignment-submissions', 'Assignment submission and grading status.'),
            $this->link('Course Reviews', 'admin.reports.exports.course-reviews', 'Student feedback moderation export.'),
        ];
    }

    public function students(array $filters = []): StreamedResponse
    {
        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->withCount(['enrollments', 'certificates'])
            ->orderBy('id');

        $this->applyDate($query, $filters);

        return $this->download('students', [
            'ID',
            'Name',
            'Email',
            'Enrollments',
            'Certificates',
            'Joined At',
        ], $query, fn (User $user): array => [
            $user->id,
            $user->name,
            $user->email,
            $user->enrollments_count,
            $user->certificates_count,
            $this->date($user->created_at),
        ]);
    }

    public function enrollments(array $filters = []): StreamedResponse
    {
        $query = Enrollment::query()
            ->with(['user', 'course.courseCompletions'])
            ->orderBy('id');

        $this->applyDate($query, $filters, 'enrolled_at');
        $this->applyStatus($query, $filters);
        $this->applyCourse($query, $filters);

        return $this->download('enrollments', [
            'ID',
            'Student',
            'Student Email',
            'Course',
            'Status',
            'Progress Percent',
            'Enrolled At',
            'Completed At',
        ], $query, fn (Enrollment $enrollment): array => [
            $enrollment->id,
            $enrollment->user?->name,
            $enrollment->user?->email,
            $enrollment->course?->title,
            $enrollment->status,
            $enrollment->course?->courseCompletions
                ? (int) ($enrollment->course->courseCompletions->firstWhere('user_id', $enrollment->user_id)?->lesson_percentage ?? 0)
                : 0,
            $this->date($enrollment->enrolled_at),
            $this->date($enrollment->completed_at),
        ]);
    }

    public function payments(array $filters = []): StreamedResponse
    {
        $query = Payment::query()
            ->with(['user', 'course', 'paymentMethod', 'reviewer', 'subscription.subscriptionPlan'])
            ->orderBy('id');

        $this->applyDate($query, $filters);
        $this->applyStatus($query, $filters);
        $this->applyCourse($query, $filters);

        return $this->download('payments', [
            'ID',
            'Student',
            'Student Email',
            'Purpose',
            'Course',
            'Subscription Plan',
            'Amount',
            'Currency',
            'Status',
            'Reference',
            'Payment Method',
            'Reviewed By',
            'Submitted At',
            'Reviewed At',
        ], $query, fn (Payment $payment): array => [
            $payment->id,
            $payment->user?->name,
            $payment->user?->email,
            $payment->purpose,
            $payment->course?->title,
            $payment->subscription?->subscriptionPlan?->name,
            $payment->amount,
            $payment->currency,
            $payment->status,
            $payment->reference,
            $payment->paymentMethod?->name ?? $payment->providerLabel(),
            $payment->reviewer?->name,
            $this->date($payment->submitted_at),
            $this->date($payment->reviewed_at),
        ]);
    }

    public function subscriptions(array $filters = []): StreamedResponse
    {
        $query = Subscription::query()
            ->with(['user', 'subscriptionPlan', 'payment'])
            ->orderBy('id');

        $this->applyDate($query, $filters);
        $this->applyStatus($query, $filters);

        return $this->download('subscriptions', [
            'ID',
            'Student',
            'Student Email',
            'Plan',
            'Status',
            'Payment Status',
            'Starts At',
            'Ends At',
            'Cancelled At',
        ], $query, fn (Subscription $subscription): array => [
            $subscription->id,
            $subscription->user?->name,
            $subscription->user?->email,
            $subscription->subscriptionPlan?->name,
            $subscription->statusLabel(),
            $subscription->payment?->status,
            $this->date($subscription->starts_at),
            $this->date($subscription->ends_at),
            $this->date($subscription->cancelled_at),
        ]);
    }

    public function certificates(array $filters = []): StreamedResponse
    {
        $query = Certificate::query()
            ->with(['user', 'course'])
            ->orderBy('id');

        $this->applyDate($query, $filters, 'issued_at');
        $this->applyStatus($query, $filters);
        $this->applyCourse($query, $filters);

        return $this->download('certificates', [
            'ID',
            'Student',
            'Student Email',
            'Course',
            'Certificate Number',
            'Verification URL',
            'Score',
            'Status',
            'Issued At',
            'Revoked At',
        ], $query, fn (Certificate $certificate): array => [
            $certificate->id,
            $certificate->student_name ?: $certificate->user?->name,
            $certificate->user?->email,
            $certificate->course_title ?: $certificate->course?->title,
            $certificate->certificate_number,
            route('certificates.verify', $certificate->verification_code),
            $certificate->score,
            $certificate->status,
            $this->date($certificate->issued_at),
            $this->date($certificate->revoked_at),
        ]);
    }

    public function quizAttempts(array $filters = []): StreamedResponse
    {
        $query = QuizAttempt::query()
            ->with(['user', 'quiz.lesson.module.course'])
            ->orderBy('id');

        $this->applyDate($query, $filters);
        $this->applyStatus($query, $filters);
        $this->applyNestedCourse($query, $filters, 'quiz.lesson.module');

        return $this->download('quiz-attempts', [
            'ID',
            'Student',
            'Student Email',
            'Course',
            'Quiz',
            'Score',
            'Total Points',
            'Percentage',
            'Status',
            'Started At',
            'Submitted At',
        ], $query, fn (QuizAttempt $attempt): array => [
            $attempt->id,
            $attempt->user?->name,
            $attempt->user?->email,
            $attempt->quiz?->lesson?->module?->course?->title,
            $attempt->quiz?->title,
            $attempt->score,
            $attempt->total_points,
            $attempt->percentage,
            $attempt->status,
            $this->date($attempt->started_at),
            $this->date($attempt->submitted_at),
        ]);
    }

    public function assignmentSubmissions(array $filters = []): StreamedResponse
    {
        $query = AssignmentSubmission::query()
            ->with(['user', 'assignment.lesson.module.course'])
            ->orderBy('id');

        $this->applyDate($query, $filters);
        $this->applyStatus($query, $filters);
        $this->applyNestedCourse($query, $filters, 'assignment.lesson.module');

        return $this->download('assignment-submissions', [
            'ID',
            'Student',
            'Student Email',
            'Course',
            'Assignment',
            'Status',
            'Score',
            'Submitted At',
            'Graded At',
        ], $query, fn (AssignmentSubmission $submission): array => [
            $submission->id,
            $submission->user?->name,
            $submission->user?->email,
            $submission->assignment?->lesson?->module?->course?->title,
            $submission->assignment?->title,
            $submission->status,
            $submission->score,
            $this->date($submission->submitted_at),
            $this->date($submission->graded_at),
        ]);
    }

    public function courseReviews(array $filters = []): StreamedResponse
    {
        $query = CourseReview::query()
            ->with(['user', 'course'])
            ->orderBy('id');

        $this->applyDate($query, $filters);
        $this->applyStatus($query, $filters);
        $this->applyCourse($query, $filters);

        return $this->download('course-reviews', [
            'ID',
            'Student',
            'Student Email',
            'Course',
            'Rating',
            'Comment',
            'Status',
            'Submitted At',
            'Updated At',
        ], $query, fn (CourseReview $review): array => [
            $review->id,
            $review->user?->name,
            $review->user?->email,
            $review->course?->title,
            $review->rating,
            $review->comment,
            $review->status,
            $this->date($review->created_at),
            $this->date($review->updated_at),
        ]);
    }

    private function download(string $name, array $headers, Builder $query, callable $map): StreamedResponse
    {
        $filename = 'mk-scholars-'.$name.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $query, $map): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            $query->chunkById(500, function ($records) use ($handle, $map): void {
                foreach ($records as $record) {
                    fputcsv($handle, array_map(fn ($value): string => $this->csvValue($value), $map($record)));
                }
            });

            fclose($handle);
        }, $filename, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function link(string $label, string $route, string $description): array
    {
        return [
            'label' => $label,
            'url' => route($route),
            'description' => $description,
        ];
    }

    private function applyDate(Builder $query, array $filters, string $column = 'created_at'): void
    {
        $from = $filters['from'] ?? $filters['date_from'] ?? null;
        $to = $filters['to'] ?? $filters['date_to'] ?? null;

        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }
    }

    private function applyStatus(Builder $query, array $filters): void
    {
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
    }

    private function applyCourse(Builder $query, array $filters): void
    {
        if ($filters['course_id'] ?? null) {
            $query->where('course_id', $filters['course_id']);
        }
    }

    private function applyNestedCourse(Builder $query, array $filters, string $relation): void
    {
        if ($filters['course_id'] ?? null) {
            $query->whereHas($relation, fn (Builder $courseQuery) => $courseQuery->where('course_id', $filters['course_id']));
        }
    }

    private function date(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
