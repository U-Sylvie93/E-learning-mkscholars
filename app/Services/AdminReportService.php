<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\MentorAssignment;
use App\Models\MentorCheckIn;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    public function index(): array
    {
        return [
            'title' => 'Admin Reports',
            'description' => 'A read-only command center for MK Scholars platform activity.',
            'cards' => [
                $this->card('Students', User::query()->where('role', User::ROLE_STUDENT)->count(), 'Registered learners'),
                $this->card('Courses', Course::query()->count(), 'Total courses'),
                $this->card('Payments', Payment::query()->where('status', Payment::STATUS_PENDING)->count(), 'Pending review'),
                $this->card('Certificates', Certificate::query()->where('status', Certificate::STATUS_ISSUED)->count(), 'Issued certificates'),
                $this->card('Notifications', AppNotification::query()->whereNull('read_at')->count(), 'Unread in-app items'),
            ],
            'links' => $this->reportLinks(),
            'exports' => app(AdminCsvExportService::class)->exportLinks(),
            'tables' => [
                [
                    'title' => 'Recent enrollments',
                    'columns' => ['Student', 'Course', 'Status', 'Date'],
                    'rows' => Enrollment::query()
                        ->with(['user', 'course'])
                        ->latest('enrolled_at')
                        ->take(8)
                        ->get()
                        ->map(fn (Enrollment $enrollment): array => [
                            $enrollment->user?->name ?? 'Student',
                            $enrollment->course?->title ?? 'Course',
                            str($enrollment->status)->headline()->toString(),
                            $enrollment->enrolled_at?->format('M j, Y') ?? 'N/A',
                        ])
                        ->all(),
                ],
                [
                    'title' => 'Pending payments',
                    'columns' => ['Student', 'Course', 'Amount', 'Submitted'],
                    'rows' => Payment::query()
                        ->with(['user', 'course'])
                        ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
                        ->latest('submitted_at')
                        ->take(8)
                        ->get()
                        ->map(fn (Payment $payment): array => [
                            $payment->user?->name ?? 'Student',
                            $payment->course?->title ?? 'Course',
                            number_format((float) $payment->amount, 0).' '.$payment->currency,
                            $payment->submitted_at?->format('M j, Y') ?? 'Not submitted',
                        ])
                        ->all(),
                ],
            ],
        ];
    }

    public function students(array $filters): array
    {
        return [
            'title' => 'Student Report',
            'description' => 'Student registration, enrollment, payments, and completion readiness.',
            'filters' => $this->baseFilters(),
            'cards' => [
                $this->card('Total students', User::query()->where('role', User::ROLE_STUDENT)->count()),
                $this->card('Active students', Enrollment::query()->where('status', Enrollment::STATUS_ACTIVE)->distinct('user_id')->count('user_id'), 'With active enrollment'),
                $this->card('Enrolled students', Enrollment::query()->distinct('user_id')->count('user_id')),
                $this->card('Pending payments', Payment::query()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])->distinct('user_id')->count('user_id'), 'Unique students'),
                $this->card('Certificate eligible', CourseCompletion::query()->where('is_eligible_for_certificate', true)->distinct('user_id')->count('user_id')),
                $this->card('Incomplete courses', CourseCompletion::query()->where('is_eligible_for_certificate', false)->count(), 'Student-course records'),
            ],
            'tables' => [
                [
                    'title' => 'Students with course activity',
                    'columns' => ['Student', 'Enrollments', 'Eligible completions', 'Pending payments'],
                    'rows' => User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->withCount([
                            'enrollments',
                            'courseCompletions as eligible_completions_count' => fn ($query) => $query->where('is_eligible_for_certificate', true),
                            'payments as pending_payments_count' => fn ($query) => $query->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED]),
                        ])
                        ->orderByDesc('enrollments_count')
                        ->take(12)
                        ->get()
                        ->map(fn (User $user): array => [$user->name, $user->enrollments_count, $user->eligible_completions_count, $user->pending_payments_count])
                        ->all(),
                ],
            ],
        ];
    }

    public function courses(array $filters): array
    {
        $courses = Course::query()
            ->withCount(['enrollments', 'certificates'])
            ->withAvg('courseCompletions', 'lesson_percentage')
            ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->whereKey($courseId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $courseRows = $courses->get()->map(function (Course $course): array {
            $quizTotal = QuizAttempt::query()
                ->whereHas('quiz.lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->count();
            $quizPassed = QuizAttempt::query()
                ->whereHas('quiz.lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->where('status', QuizAttempt::STATUS_PASSED)
                ->count();
            $assignmentCount = AssignmentSubmission::query()
                ->whereHas('assignment.lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->count();

            return [
                $course->title,
                $course->status,
                $course->enrollments_count,
                $this->formatPercent((int) round($course->course_completions_avg_lesson_percentage ?? 0)),
                $this->formatPercent($this->percent($quizPassed, $quizTotal)),
                $assignmentCount,
                $course->certificates_count,
            ];
        })->all();

        return [
            'title' => 'Course Report',
            'description' => 'Course publishing, enrollment, completion, assessments, and certificates.',
            'filters' => $this->courseStatusFilters(Course::STATUSES),
            'cards' => [
                $this->card('Total courses', Course::query()->count()),
                $this->card('Published courses', Course::query()->where('status', Course::STATUS_PUBLISHED)->count()),
                $this->card('Total enrollments', Enrollment::query()->count()),
                $this->card('Average completion', $this->formatPercent((int) round(CourseCompletion::query()->avg('lesson_percentage') ?? 0))),
            ],
            'tables' => [[
                'title' => 'Course performance',
                'columns' => ['Course', 'Status', 'Enrollments', 'Avg completion', 'Quiz pass rate', 'Assignment submissions', 'Certificates'],
                'rows' => $courseRows,
            ]],
        ];
    }

    public function payments(array $filters): array
    {
        $payments = Payment::query();
        $this->applyDate($payments, $filters);
        $this->applyCourse($payments, $filters);
        $this->applyStatus($payments, $filters);

        $approved = Payment::query()->where('status', Payment::STATUS_APPROVED);
        $this->applyDate($approved, $filters);
        $this->applyCourse($approved, $filters);

        return [
            'title' => 'Payment Report',
            'description' => 'Manual payment proof, review status, and approved revenue.',
            'filters' => $this->courseStatusFilters([Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED, Payment::STATUS_APPROVED, Payment::STATUS_REJECTED, Payment::STATUS_CANCELLED], true),
            'cards' => [
                $this->card('Total payments', (clone $payments)->count()),
                $this->card('Pending', (clone $payments)->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])->count()),
                $this->card('Approved', (clone $payments)->where('status', Payment::STATUS_APPROVED)->count()),
                $this->card('Rejected', (clone $payments)->where('status', Payment::STATUS_REJECTED)->count()),
                $this->card('Approved revenue', number_format((float) $approved->sum('amount'), 0).' RWF'),
                $this->card('Active subscriptions', Subscription::query()->where('status', Subscription::STATUS_ACTIVE)->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))->count()),
                $this->card('Pending subscription payments', Payment::query()->where('purpose', Payment::PURPOSE_SUBSCRIPTION)->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])->count()),
                $this->card('Subscription revenue', number_format((float) Payment::query()->where('purpose', Payment::PURPOSE_SUBSCRIPTION)->where('status', Payment::STATUS_APPROVED)->sum('amount'), 0).' RWF'),
                $this->card('Expiring subscriptions', Subscription::query()->where('status', Subscription::STATUS_ACTIVE)->whereBetween('ends_at', [now(), now()->addDays(7)])->count(), 'Next 7 days'),
            ],
            'tables' => [
                [
                    'title' => 'Subscription payment health',
                    'columns' => ['Status', 'Payments'],
                    'rows' => Payment::query()
                        ->select('status', DB::raw('count(*) as payments_count'))
                        ->where('purpose', Payment::PURPOSE_SUBSCRIPTION)
                        ->groupBy('status')
                        ->orderByDesc('payments_count')
                        ->get()
                        ->map(fn (Payment $payment): array => [str($payment->status)->headline()->toString(), $payment->payments_count])
                        ->all(),
                ],
                [
                    'title' => 'Revenue by provider',
                    'columns' => ['Provider', 'Revenue', 'Payments'],
                    'rows' => Payment::query()
                        ->select(DB::raw("COALESCE(provider, 'manual') as provider"), DB::raw('sum(amount) as total'), DB::raw('count(*) as payments_count'))
                        ->where('status', Payment::STATUS_APPROVED)
                        ->groupBy(DB::raw("COALESCE(provider, 'manual')"))
                        ->orderByDesc('total')
                        ->get()
                        ->map(fn (Payment $payment): array => [$payment->providerLabel(), number_format((float) $payment->total, 0).' RWF', $payment->payments_count])
                        ->all(),
                ],
                [
                    'title' => 'Revenue by course',
                    'columns' => ['Course', 'Revenue', 'Payments'],
                    'rows' => Payment::query()
                        ->select('course_id', DB::raw('sum(amount) as total'), DB::raw('count(*) as payments_count'))
                        ->where('status', Payment::STATUS_APPROVED)
                        ->with('course')
                        ->groupBy('course_id')
                        ->orderByDesc('total')
                        ->take(12)
                        ->get()
                        ->map(fn (Payment $payment): array => [$payment->course?->title ?? 'Other', number_format((float) $payment->total, 0).' RWF', $payment->payments_count])
                        ->all(),
                ],
                [
                    'title' => 'Revenue by payment method',
                    'columns' => ['Method', 'Revenue', 'Payments'],
                    'rows' => Payment::query()
                        ->select('payment_method_id', DB::raw('sum(amount) as total'), DB::raw('count(*) as payments_count'))
                        ->where('status', Payment::STATUS_APPROVED)
                        ->with('paymentMethod')
                        ->groupBy('payment_method_id')
                        ->orderByDesc('total')
                        ->take(12)
                        ->get()
                        ->map(fn (Payment $payment): array => [$payment->paymentMethod?->name ?? 'Unspecified', number_format((float) $payment->total, 0).' RWF', $payment->payments_count])
                        ->all(),
                ],
            ],
        ];
    }

    public function learning(array $filters): array
    {
        $attempts = QuizAttempt::query();
        $submissions = AssignmentSubmission::query();

        if ($filters['course_id'] ?? null) {
            $attempts->whereHas('quiz.lesson.module', fn ($query) => $query->where('course_id', $filters['course_id']));
            $submissions->whereHas('assignment.lesson.module', fn ($query) => $query->where('course_id', $filters['course_id']));
        }

        return [
            'title' => 'Quiz and Assignment Report',
            'description' => 'Assessment attempts, quiz performance, assignment submissions, and grading workload.',
            'filters' => $this->courseFilters(),
            'cards' => [
                $this->card('Quiz attempts', (clone $attempts)->count()),
                $this->card('Average quiz score', $this->formatPercent((int) round((clone $attempts)->avg('percentage') ?? 0))),
                $this->card('Passed attempts', (clone $attempts)->where('status', QuizAttempt::STATUS_PASSED)->count()),
                $this->card('Failed attempts', (clone $attempts)->where('status', QuizAttempt::STATUS_FAILED)->count()),
                $this->card('Submitted assignments', (clone $submissions)->count()),
                $this->card('Pending grading', (clone $submissions)->where('status', AssignmentSubmission::STATUS_SUBMITTED)->count()),
            ],
            'tables' => [[
                'title' => 'Recent assessment activity',
                'columns' => ['Type', 'Student', 'Item', 'Status', 'Score'],
                'rows' => collect()
                    ->merge(QuizAttempt::query()->with(['user', 'quiz'])->latest()->take(8)->get()->map(fn (QuizAttempt $attempt): array => ['Quiz', $attempt->user?->name ?? 'Student', $attempt->quiz?->title ?? 'Quiz', $attempt->status, $attempt->percentage.'%']))
                    ->merge(AssignmentSubmission::query()->with(['user', 'assignment'])->latest()->take(8)->get()->map(fn (AssignmentSubmission $submission): array => ['Assignment', $submission->user?->name ?? 'Student', $submission->assignment?->title ?? 'Assignment', $submission->status, $submission->score ?? 'N/A']))
                    ->take(12)
                    ->all(),
            ]],
        ];
    }

    public function liveClasses(array $filters): array
    {
        $classes = LiveClass::query();
        $this->applyDate($classes, $filters, 'starts_at');
        $this->applyStatus($classes, $filters);

        $attended = LiveClassAttendance::query()->where('status', LiveClassAttendance::STATUS_ATTENDED)->count();
        $missed = LiveClassAttendance::query()->where('status', LiveClassAttendance::STATUS_MISSED)->count();

        return [
            'title' => 'Live Class Report',
            'description' => 'Live learning schedule, attendance, missed sessions, and course-level rates.',
            'filters' => $this->statusFilters([LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE, LiveClass::STATUS_COMPLETED, LiveClass::STATUS_CANCELLED], true),
            'cards' => [
                $this->card('Upcoming classes', (clone $classes)->whereIn('status', [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE])->where('starts_at', '>=', now())->count()),
                $this->card('Completed classes', (clone $classes)->where('status', LiveClass::STATUS_COMPLETED)->count()),
                $this->card('Attendance count', $attended),
                $this->card('Missed count', $missed),
                $this->card('Attendance rate', $this->formatPercent($this->percent($attended, $attended + $missed))),
            ],
            'tables' => [[
                'title' => 'Recent live classes',
                'columns' => ['Class', 'Course', 'Status', 'Starts', 'Attendance'],
                'rows' => LiveClass::query()
                    ->with(['course', 'module.course', 'lesson.module.course'])
                    ->withCount(['attendances' => fn ($query) => $query->where('status', LiveClassAttendance::STATUS_ATTENDED)])
                    ->latest('starts_at')
                    ->take(12)
                    ->get()
                    ->map(fn (LiveClass $liveClass): array => [$liveClass->title, $liveClass->associatedCourse()?->title ?? 'Unlinked', $liveClass->status, $liveClass->starts_at?->format('M j, Y g:i A') ?? 'N/A', $liveClass->attendances_count])
                    ->all(),
            ]],
        ];
    }

    public function mentorship(array $filters): array
    {
        return [
            'title' => 'Mentorship Report',
            'description' => 'Mentor assignments, weekly check-ins, feedback activity, and workload.',
            'filters' => $this->baseFilters(),
            'cards' => [
                $this->card('Active assignments', MentorAssignment::query()->where('status', MentorAssignment::STATUS_ACTIVE)->count()),
                $this->card('Scheduled check-ins', MentorCheckIn::query()->where('status', MentorCheckIn::STATUS_SCHEDULED)->count()),
                $this->card('Completed check-ins', MentorCheckIn::query()->where('status', MentorCheckIn::STATUS_COMPLETED)->count()),
                $this->card('Missed check-ins', MentorCheckIn::query()->where('status', MentorCheckIn::STATUS_MISSED)->count()),
            ],
            'tables' => [[
                'title' => 'Mentor workload',
                'columns' => ['Mentor', 'Active students', 'Scheduled check-ins', 'Completed check-ins'],
                'rows' => User::query()
                    ->where('role', User::ROLE_MENTOR)
                    ->withCount([
                        'mentorAssignments as active_students_count' => fn ($query) => $query->where('status', MentorAssignment::STATUS_ACTIVE),
                    ])
                    ->orderByDesc('active_students_count')
                    ->take(12)
                    ->get()
                    ->map(fn (User $mentor): array => [
                        $mentor->name,
                        $mentor->active_students_count,
                        MentorCheckIn::query()
                            ->where('status', MentorCheckIn::STATUS_SCHEDULED)
                            ->whereHas('mentorAssignment', fn ($query) => $query->where('mentor_id', $mentor->id))
                            ->count(),
                        MentorCheckIn::query()
                            ->where('status', MentorCheckIn::STATUS_COMPLETED)
                            ->whereHas('mentorAssignment', fn ($query) => $query->where('mentor_id', $mentor->id))
                            ->count(),
                    ])
                    ->all(),
            ]],
        ];
    }

    public function certificates(array $filters): array
    {
        return [
            'title' => 'Certificate Report',
            'description' => 'Issued and revoked credentials. Verification usage is not tracked yet.',
            'filters' => $this->courseStatusFilters([Certificate::STATUS_ISSUED, Certificate::STATUS_REVOKED]),
            'cards' => [
                $this->card('Issued certificates', Certificate::query()->where('status', Certificate::STATUS_ISSUED)->count()),
                $this->card('Revoked certificates', Certificate::query()->where('status', Certificate::STATUS_REVOKED)->count()),
                $this->card('Eligible students', CourseCompletion::query()->where('is_eligible_for_certificate', true)->count()),
            ],
            'tables' => [[
                'title' => 'Certificates by course',
                'columns' => ['Course', 'Issued', 'Revoked'],
                'rows' => Course::query()
                    ->withCount([
                        'certificates as issued_count' => fn ($query) => $query->where('status', Certificate::STATUS_ISSUED),
                        'certificates as revoked_count' => fn ($query) => $query->where('status', Certificate::STATUS_REVOKED),
                    ])
                    ->orderByDesc('issued_count')
                    ->take(12)
                    ->get()
                    ->map(fn (Course $course): array => [$course->title, $course->issued_count, $course->revoked_count])
                    ->all(),
            ]],
        ];
    }

    public function reportLinks(): array
    {
        return [
            ['label' => 'Students', 'url' => '/admin/reports/students'],
            ['label' => 'Courses', 'url' => '/admin/reports/courses'],
            ['label' => 'Payments', 'url' => '/admin/reports/payments'],
            ['label' => 'Learning', 'url' => '/admin/reports/learning'],
            ['label' => 'Live Classes', 'url' => '/admin/reports/live-classes'],
            ['label' => 'Certificates', 'url' => '/admin/reports/certificates'],
        ];
    }

    private function card(string $label, mixed $value, ?string $hint = null): array
    {
        return compact('label', 'value', 'hint');
    }

    private function applyDate(Builder $query, array $filters, string $column = 'created_at'): void
    {
        if ($filters['from'] ?? null) {
            $query->whereDate($column, '>=', $filters['from']);
        }

        if ($filters['to'] ?? null) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    private function applyCourse(Builder $query, array $filters): void
    {
        if ($filters['course_id'] ?? null) {
            $query->where('course_id', $filters['course_id']);
        }
    }

    private function applyStatus(Builder $query, array $filters): void
    {
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
    }

    private function baseFilters(): array
    {
        return [
            'date' => true,
            'courses' => [],
            'statuses' => [],
        ];
    }

    private function courseFilters(): array
    {
        return [
            'date' => false,
            'courses' => Course::query()->orderBy('title')->pluck('title', 'id')->all(),
            'statuses' => [],
        ];
    }

    private function statusFilters(array $statuses, bool $date = false): array
    {
        return [
            'date' => $date,
            'courses' => [],
            'statuses' => collect($statuses)->mapWithKeys(fn (string $status): array => [$status => str($status)->replace('_', ' ')->headline()->toString()])->all(),
        ];
    }

    private function courseStatusFilters(array $statuses, bool $date = false): array
    {
        return [
            'date' => $date,
            'courses' => Course::query()->orderBy('title')->pluck('title', 'id')->all(),
            'statuses' => collect($statuses)->mapWithKeys(fn (string $status): array => [$status => str($status)->replace('_', ' ')->headline()->toString()])->all(),
        ];
    }

    private function percent(int $part, int $total): int
    {
        return $total > 0 ? (int) round(($part / $total) * 100) : 0;
    }

    private function formatPercent(int $value): string
    {
        return $value.'%';
    }
}

