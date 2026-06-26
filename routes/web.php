<?php

use App\Models\User;
use App\Models\Academy;
use App\Models\AppNotification;
use App\Models\Assignment;
use App\Models\AssignmentQuestionAnswer;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\MentorAssignment;
use App\Models\MentorCheckIn;
use App\Models\ApplicationDocument;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\StudentApplication;
use App\Models\StudentDocument;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CourseCompletionService;
use App\Services\AppNotificationService;
use App\Services\AdminCsvExportService;
use App\Services\CertificatePdfService;
use App\Services\Payments\PaymentProviderManager;
use App\Support\CourseContentRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

$publicAcademies = function (): array {
    try {
        if (! Schema::hasTable('academies')) {
            return config('mkscholars.academies');
        }

        $academies = Academy::query()
            ->where('status', Academy::STATUS_PUBLISHED)
            ->withCount('courses')
            ->orderBy('name')
            ->get();

        if ($academies->isNotEmpty()) {
            return $academies->map->toPublicCard()->all();
        }
    } catch (Throwable) {
        return config('mkscholars.academies');
    }

    return config('mkscholars.academies');
};

$publicCourses = function (?string $academySlug = null): array {
    try {
        if (! Schema::hasTable('courses')) {
            $courses = config('mkscholars.courses');

            return $academySlug
                ? collect($courses)->where('academy_slug', $academySlug)->values()->all()
                : $courses;
        }

        $courses = Course::query()
            ->with(['academy', 'modules.lessons'])
            ->where('status', Course::STATUS_PUBLISHED)
            ->when($academySlug, fn ($query) => $query->whereHas('academy', fn ($academyQuery) => $academyQuery->where('slug', $academySlug)))
            ->latest()
            ->get();

        if ($courses->isNotEmpty()) {
            return $courses->map->toPublicCard()->all();
        }
    } catch (Throwable) {
        return config('mkscholars.courses');
    }

    return config('mkscholars.courses');
};

$publicOpportunities = function (?string $type = null, ?string $country = null): array {
    try {
        if (! Schema::hasTable('opportunities')) {
            return config('mkscholars.opportunities');
        }

        $opportunities = Opportunity::query()
            ->where('status', Opportunity::STATUS_PUBLISHED)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($country, fn ($query) => $query->where('country', $country))
            ->orderByDesc('is_featured')
            ->orderBy('deadline')
            ->latest()
            ->get();

        if ($opportunities->isNotEmpty()) {
            return $opportunities->map->toPublicCard()->all();
        }
    } catch (Throwable) {
        return config('mkscholars.opportunities');
    }

    return config('mkscholars.opportunities');
};

$publishedLessonsForCourse = function (Course $course) {
    return Lesson::query()
        ->whereHas('module', fn ($query) => $query
            ->where('course_id', $course->id)
            ->where('status', Course::STATUS_PUBLISHED))
        ->where('status', Course::STATUS_PUBLISHED)
        ->orderBy('module_id')
        ->orderBy('sort_order')
        ->get();
};

$courseProgress = function (User $user, Course $course) use ($publishedLessonsForCourse): int {
    $lessonIds = $publishedLessonsForCourse($course)->pluck('id');

    if ($lessonIds->isEmpty()) {
        return 0;
    }

    $completed = LessonProgress::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->whereIn('lesson_id', $lessonIds)
        ->where('status', LessonProgress::STATUS_COMPLETED)
        ->count();

    return (int) round(($completed / $lessonIds->count()) * 100);
};

$activeSubscriptionForCourse = function (User $user, Course $course): ?Subscription {
    return Subscription::query()
        ->with('subscriptionPlan')
        ->where('user_id', $user->id)
        ->where('status', Subscription::STATUS_ACTIVE)
        ->where(function ($query): void {
            $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })
        ->where(function ($query): void {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        })
        ->whereHas('subscriptionPlan.courses', fn ($courseQuery) => $courseQuery->whereKey($course->id))
        ->latest('starts_at')
        ->first();
};

$hasCourseAccess = function (User $user, Course $course) use ($activeSubscriptionForCourse): bool {
    $hasActiveEnrollment = Enrollment::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->where('status', Enrollment::STATUS_ACTIVE)
        ->exists();

    if ($course->isFree()) {
        return $hasActiveEnrollment;
    }

    $hasApprovedPayment = Payment::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->where('purpose', Payment::PURPOSE_COURSE)
        ->where('status', Payment::STATUS_APPROVED)
        ->exists();

    return ($hasActiveEnrollment && $hasApprovedPayment) || (bool) $activeSubscriptionForCourse($user, $course);
};

Route::get('/', fn () => view('pages.home', [
    'academies' => $publicAcademies(),
    'courses' => $publicCourses(),
    'opportunities' => $publicOpportunities(),
]))->name('home');

Route::get('/academies', fn () => view('pages.academies', [
    'academies' => $publicAcademies(),
]))->name('academies');

Route::get('/courses', fn (Request $request) => view('pages.courses', [
    'courses' => $publicCourses($request->query('academy')),
    'activeAcademy' => $request->query('academy'),
]))->name('courses');

Route::get('/courses/{slug}', function (string $slug) use ($activeSubscriptionForCourse) {
    try {
        if (! Schema::hasTable('courses')) {
            throw new RuntimeException('Courses table is not available.');
        }

        $databaseCourse = Course::query()
            ->with([
                'academy',
                'reviews' => fn ($query) => $query
                    ->where('status', CourseReview::STATUS_PUBLISHED)
                    ->with('user')
                    ->latest(),
                'modules' => fn ($query) => $query
                    ->where('status', Course::STATUS_PUBLISHED)
                    ->orderBy('sort_order')
                    ->with([
                        'lessons' => fn ($lessonQuery) => $lessonQuery
                            ->where('status', Course::STATUS_PUBLISHED)
                            ->orderBy('sort_order'),
                    ]),
            ])
            ->where('status', Course::STATUS_PUBLISHED)
            ->where('slug', $slug)
            ->first();

        if ($databaseCourse) {
            $user = Auth::user();
            $enrollment = $user
                ? Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $databaseCourse->id)
                    ->first()
                : null;
            $payment = $user
                ? Payment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $databaseCourse->id)
                    ->where('purpose', Payment::PURPOSE_COURSE)
                    ->latest()
                    ->first()
                : null;
            $hasActiveEnrollment = $enrollment?->status === Enrollment::STATUS_ACTIVE;
            $hasActiveSubscription = $user && $user->role === User::ROLE_STUDENT
                ? (bool) $activeSubscriptionForCourse($user, $databaseCourse)
                : false;
            $hasApprovedAccess = $databaseCourse->isFree() || $payment?->status === Payment::STATUS_APPROVED;
            $publishedReviews = $databaseCourse->reviews;

            $courseDetails = [
                ...$databaseCourse->toPublicCard(),
                'id' => $databaseCourse->id,
                'short_description' => $databaseCourse->short_description,
                'full_description' => $databaseCourse->full_description,
                'rendered_full_description' => CourseContentRenderer::render($databaseCourse->full_description),
                'image' => $databaseCourse->coverImageUrl(),
                'lessons_count' => $databaseCourse->modules->sum(fn ($module) => $module->lessons->count()),
                'payment_id' => $payment?->id,
                'payment_status' => $payment?->status,
                'reviews_count' => $publishedReviews->count(),
                'average_rating' => $publishedReviews->avg('rating') ? round($publishedReviews->avg('rating'), 1) : null,
                'reviews' => $publishedReviews
                    ->map(fn (CourseReview $review): array => [
                        'reviewer' => $review->user?->name ?? 'MK Scholars student',
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at?->format('M j, Y'),
                    ])
                    ->all(),
                'cta_state' => match (true) {
                    ! $user => 'guest',
                    $user->role !== User::ROLE_STUDENT => 'non_student',
                    $hasActiveSubscription => 'enrolled',
                    $hasActiveEnrollment && $hasApprovedAccess => 'enrolled',
                    ! $databaseCourse->isFree() && in_array($payment?->status, [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED], true) => 'payment_pending',
                    ! $databaseCourse->isFree() && $payment?->status === Payment::STATUS_REJECTED => 'payment_rejected',
                    ! $databaseCourse->isFree() => 'paid_not_started',
                    default => 'student_not_enrolled',
                },
                'modules' => $databaseCourse->modules
                    ->map(fn ($module): array => [
                        'title' => $module->title,
                        'summary' => $module->summary,
                        'sort_order' => $module->sort_order,
                        'lessons' => $module->lessons
                            ->map(fn ($lesson): array => [
                                'title' => $lesson->title,
                                'lesson_type' => $lesson->lesson_type,
                                'duration_minutes' => $lesson->duration_minutes,
                                'is_free_preview' => $lesson->is_free_preview,
                            ])
                            ->all(),
                    ])
                    ->all(),
            ];

            return view('pages.course-details', [
                'course' => $courseDetails,
                'relatedCourses' => Course::query()
                    ->with(['academy', 'modules.lessons'])
                    ->where('status', Course::STATUS_PUBLISHED)
                    ->whereKeyNot($databaseCourse->getKey())
                    ->latest()
                    ->take(2)
                    ->get()
                    ->map->toPublicCard()
                    ->all(),
                'relatedOpportunities' => Schema::hasTable('opportunities')
                    ? Opportunity::query()
                        ->where('status', Opportunity::STATUS_PUBLISHED)
                        ->orderByDesc('is_featured')
                        ->orderBy('deadline')
                        ->take(3)
                        ->get()
                    : collect(),
            ]);
        }
    } catch (Throwable) {
        //
    }

    $course = collect(config('mkscholars.courses'))->firstWhere('slug', $slug);

    abort_if(! $course, 404);

    return view('pages.course-details', [
        'course' => [
            ...$course,
            'rendered_full_description' => CourseContentRenderer::render($course['full_description'] ?? null),
        ],
        'relatedCourses' => collect(config('mkscholars.courses'))
            ->reject(fn (array $item): bool => $item['slug'] === $slug)
            ->take(2)
            ->all(),
            'relatedOpportunities' => collect(),
            'reviews' => [],
        ]);
})->name('courses.show');

Route::get('/opportunities', fn (Request $request) => view('pages.opportunities', [
    'opportunities' => $publicOpportunities($request->query('type'), $request->query('country')),
]))->name('opportunities');

Route::get('/opportunities/{slug}', function (string $slug) {
    if (! Schema::hasTable('opportunities')) {
        abort(404);
    }

    $opportunity = Opportunity::query()
        ->with(['requirements' => fn ($query) => $query->orderBy('sort_order')])
        ->where('status', Opportunity::STATUS_PUBLISHED)
        ->where('slug', $slug)
        ->firstOrFail();

    $user = Auth::user();
    $application = $user
        ? StudentApplication::query()
            ->where('user_id', $user->id)
            ->where('opportunity_id', $opportunity->id)
            ->first()
        : null;

    return view('pages.opportunity-details', [
        'opportunity' => $opportunity,
        'application' => $application,
        'ctaState' => match (true) {
            ! $user => 'guest',
            $user->role !== User::ROLE_STUDENT => 'non_student',
            (bool) $application => 'application_started',
            default => 'student_can_apply',
        },
    ]);
})->name('opportunities.show');

Route::get('/pricing', function () {
    try {
        if (Schema::hasTable('subscription_plans')) {
            $subscriptionPlans = SubscriptionPlan::query()
                ->withCount('courses')
                ->where('status', SubscriptionPlan::STATUS_ACTIVE)
                ->orderBy('price_amount')
                ->get();

            if ($subscriptionPlans->isNotEmpty()) {
                return view('pages.pricing', [
                    'plans' => $subscriptionPlans,
                    'usingDatabasePlans' => true,
                ]);
            }
        }
    } catch (Throwable) {
        //
    }

    return view('pages.pricing', [
        'plans' => config('mkscholars.pricing'),
        'usingDatabasePlans' => false,
    ]);
})->name('pricing');

Route::view('/about', 'pages.about')->name('about');

Route::view('/contact', 'pages.contact')->name('contact');

Route::get('/certificates/verify/{verification_code}', function (string $verification_code) {
    $certificate = Certificate::query()
        ->with('skills')
        ->where('verification_code', $verification_code)
        ->first();

    return view('pages.certificate-verify', [
        'certificate' => $certificate,
        'isValid' => $certificate?->status === Certificate::STATUS_ISSUED,
    ]);
})->name('certificates.verify');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
});

Route::view('/setup-admin', 'auth.setup-admin')->name('setup-admin');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::post('/payments/webhooks/{provider}', function (string $provider) {
    return response()->json([
        'message' => 'Payment provider callbacks are not implemented yet.',
        'provider' => $provider,
    ], 501);
})->name('payments.webhooks');

Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])
    ->prefix('admin/reports/exports')
    ->name('admin.reports.exports.')
    ->group(function (): void {
        Route::get('/students', fn (Request $request) => app(AdminCsvExportService::class)->students($request->query()))->name('students');
        Route::get('/enrollments', fn (Request $request) => app(AdminCsvExportService::class)->enrollments($request->query()))->name('enrollments');
        Route::get('/payments', fn (Request $request) => app(AdminCsvExportService::class)->payments($request->query()))->name('payments');
        Route::get('/subscriptions', fn (Request $request) => app(AdminCsvExportService::class)->subscriptions($request->query()))->name('subscriptions');
        Route::get('/certificates', fn (Request $request) => app(AdminCsvExportService::class)->certificates($request->query()))->name('certificates');
        Route::get('/applications', fn (Request $request) => app(AdminCsvExportService::class)->applications($request->query()))->name('applications');
        Route::get('/quiz-attempts', fn (Request $request) => app(AdminCsvExportService::class)->quizAttempts($request->query()))->name('quiz-attempts');
        Route::get('/assignment-submissions', fn (Request $request) => app(AdminCsvExportService::class)->assignmentSubmissions($request->query()))->name('assignment-submissions');
        Route::get('/course-reviews', fn (Request $request) => app(AdminCsvExportService::class)->courseReviews($request->query()))->name('course-reviews');
    });

$settingsPage = function (string $role, string $dashboardRoute) {
    return view('account.settings', [
        'user' => Auth::user(),
        'role' => $role,
        'dashboardRoute' => $dashboardRoute,
        'profileRoute' => $role.'.settings.profile',
        'passwordRoute' => $role.'.settings.password',
    ]);
};

$updateProfile = function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $request->user()->update([
        'name' => $validated['name'],
    ]);

    return back()->with('profile_status', 'Your profile name has been updated.');
};

$updatePassword = function (Request $request) {
    $validated = $request->validate([
        'current_password' => ['required', 'string'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if (! Hash::check($validated['current_password'], $request->user()->password)) {
        throw ValidationException::withMessages([
            'current_password' => 'The current password is incorrect.',
        ]);
    }

    $request->user()->update([
        'password' => $validated['password'],
    ]);

    return back()->with('password_status', 'Your password has been updated.');
};
Route::middleware('auth')->group(function () use ($publishedLessonsForCourse, $courseProgress, $activeSubscriptionForCourse, $hasCourseAccess, $settingsPage, $updateProfile, $updatePassword): void {
    Route::get('/student/dashboard', function () {
        $user = Auth::user();
        $notificationService = app(AppNotificationService::class);

        $mentorAssignment = MentorAssignment::query()
            ->with(['mentor', 'course', 'checkIns' => fn ($query) => $query->orderBy('scheduled_at')])
            ->where('student_id', $user->id)
            ->where('status', MentorAssignment::STATUS_ACTIVE)
            ->latest('assigned_at')
            ->first();

        $expiringSubscriptionReminder = Schema::hasTable('subscriptions')
            ? Subscription::query()
                ->with('subscriptionPlan')
                ->where('user_id', $user->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->orderBy('ends_at')
                ->first()
            : null;

        if ($expiringSubscriptionReminder) {
            $notificationService->createForUser($user, [
                'title' => 'Subscription expiring soon',
                'message' => 'Your '.$expiringSubscriptionReminder->subscriptionPlan?->name.' subscription expires on '.$expiringSubscriptionReminder->ends_at?->format('M j, Y').'.',
                'type' => AppNotification::TYPE_REMINDER,
                'category' => AppNotification::CATEGORY_PAYMENT,
                'action_url' => route('student.subscriptions.show', $expiringSubscriptionReminder),
            ]);
        }

        return view('student.dashboard', [
            'mentorAssignment' => $mentorAssignment,
            'nextCheckIn' => $mentorAssignment?->checkIns
                ->where('status', MentorCheckIn::STATUS_SCHEDULED)
                ->first(),
            'latestFeedback' => $mentorAssignment?->checkIns
                ->where('status', MentorCheckIn::STATUS_COMPLETED)
                ->filter(fn (MentorCheckIn $checkIn): bool => filled($checkIn->mentor_feedback))
                ->last(),
            'certificateCount' => Certificate::query()
                ->where('user_id', $user->id)
                ->where('status', Certificate::STATUS_ISSUED)
                ->count(),
            'latestCertificate' => Certificate::query()
                ->where('user_id', $user->id)
                ->where('status', Certificate::STATUS_ISSUED)
                ->latest('issued_at')
                ->first(),
            'newOpportunitiesCount' => Schema::hasTable('opportunities')
                ? Opportunity::query()->where('status', Opportunity::STATUS_PUBLISHED)->count()
                : 0,
            'pendingApplicationsCount' => Schema::hasTable('student_applications')
                ? StudentApplication::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', [
                        StudentApplication::STATUS_DRAFT,
                        StudentApplication::STATUS_SUBMITTED,
                        StudentApplication::STATUS_UNDER_REVIEW,
                    ])
                    ->count()
                : 0,
            'nearestOpportunityDeadline' => Schema::hasTable('opportunities')
                ? Opportunity::query()
                    ->where('status', Opportunity::STATUS_PUBLISHED)
                    ->whereNotNull('deadline')
                    ->whereDate('deadline', '>=', now()->toDateString())
                    ->orderBy('deadline')
                    ->first()
                : null,
            'recentOpportunities' => Schema::hasTable('opportunities')
                ? Opportunity::query()
                    ->where('status', Opportunity::STATUS_PUBLISHED)
                    ->latest()
                    ->take(3)
                    ->get()
                : collect(),
            'pendingPaymentsCount' => Schema::hasTable('payments')
                ? Payment::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
                    ->count()
                : 0,
            'approvedPaymentsCount' => Schema::hasTable('payments')
                ? Payment::query()
                    ->where('user_id', $user->id)
                    ->where('status', Payment::STATUS_APPROVED)
                    ->count()
                : 0,
            'rejectedPaymentsCount' => Schema::hasTable('payments')
                ? Payment::query()
                    ->where('user_id', $user->id)
                    ->where('status', Payment::STATUS_REJECTED)
                    ->count()
                : 0,
            'activeSubscription' => Schema::hasTable('subscriptions')
                ? Subscription::query()
                    ->with('subscriptionPlan')
                    ->where('user_id', $user->id)
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->where(function ($query): void {
                        $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                    })
                    ->latest('starts_at')
                    ->first()
                : null,
            'expiringSubscription' => Schema::hasTable('subscriptions')
                ? Subscription::query()
                    ->with('subscriptionPlan')
                    ->where('user_id', $user->id)
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->whereBetween('ends_at', [now(), now()->addDays(7)])
                    ->orderBy('ends_at')
                    ->first()
                : null,
            'expiredSubscription' => Schema::hasTable('subscriptions')
                ? Subscription::query()
                    ->with('subscriptionPlan')
                    ->where('user_id', $user->id)
                    ->where(function ($query): void {
                        $query->where('status', Subscription::STATUS_EXPIRED)
                            ->orWhere(fn ($activeQuery) => $activeQuery
                                ->where('status', Subscription::STATUS_ACTIVE)
                                ->whereNotNull('ends_at')
                                ->where('ends_at', '<=', now()));
                    })
                    ->latest('ends_at')
                    ->first()
                : null,
            'pendingSubscription' => Schema::hasTable('subscriptions')
                ? Subscription::query()
                    ->with(['subscriptionPlan', 'payment'])
                    ->where('user_id', $user->id)
                    ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_REJECTED])
                    ->latest()
                    ->first()
                : null,
            'coursesAwaitingReview' => Schema::hasTable('course_reviews')
                ? Enrollment::query()
                    ->with('course')
                    ->where('user_id', $user->id)
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->whereDoesntHave('course.reviews', fn ($query) => $query->where('user_id', $user->id))
                    ->latest('enrolled_at')
                    ->take(3)
                    ->get()
                    ->pluck('course')
                    ->filter()
                : collect(),
            'unreadNotifications' => Schema::hasTable('app_notifications')
                ? $notificationService->visibleFor($user)->whereNull('read_at')->take(5)->get()
                : collect(),
            'unreadNotificationsCount' => Schema::hasTable('app_notifications')
                ? $notificationService->unreadCount($user)
                : 0,
            'upcomingLiveClasses' => Schema::hasTable('live_classes')
                ? LiveClass::query()
                    ->whereIn('status', [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE])
                    ->where('starts_at', '>=', now())
                    ->where(function ($query) use ($user): void {
                        $query->whereHas('course.enrollments', fn ($enrollmentQuery) => $enrollmentQuery
                            ->where('user_id', $user->id)
                            ->where('status', Enrollment::STATUS_ACTIVE))
                            ->orWhereHas('module.course.enrollments', fn ($enrollmentQuery) => $enrollmentQuery
                                ->where('user_id', $user->id)
                                ->where('status', Enrollment::STATUS_ACTIVE))
                            ->orWhereHas('lesson.module.course.enrollments', fn ($enrollmentQuery) => $enrollmentQuery
                                ->where('user_id', $user->id)
                                ->where('status', Enrollment::STATUS_ACTIVE));
                    })
                    ->orderBy('starts_at')
                    ->take(3)
                    ->get()
                : collect(),
            'upcomingAssignmentDeadlines' => Schema::hasTable('assignments')
                ? Assignment::query()
                    ->with('lesson.module.course')
                    ->where('status', Assignment::STATUS_PUBLISHED)
                    ->whereNotNull('due_days_after_enrollment')
                    ->whereHas('lesson.module.course.enrollments', fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('status', Enrollment::STATUS_ACTIVE))
                    ->get()
                    ->map(function (Assignment $assignment) use ($user): array {
                        $enrollment = Enrollment::query()
                            ->where('user_id', $user->id)
                            ->where('course_id', $assignment->lesson?->module?->course_id)
                            ->where('status', Enrollment::STATUS_ACTIVE)
                            ->first();

                        return [
                            'assignment' => $assignment,
                            'due_at' => $enrollment?->enrolled_at?->copy()->addDays($assignment->due_days_after_enrollment),
                        ];
                    })
                    ->filter(fn (array $item): bool => $item['due_at'] && $item['due_at']->isFuture())
                    ->sortBy('due_at')
                    ->take(3)
                    ->values()
                : collect(),
            'upcomingMentorCheckIns' => $mentorAssignment
                ? $mentorAssignment->checkIns
                    ->where('status', MentorCheckIn::STATUS_SCHEDULED)
                    ->filter(fn (MentorCheckIn $checkIn): bool => $checkIn->scheduled_at && $checkIn->scheduled_at->isFuture())
                    ->take(3)
                    ->values()
                : collect(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.dashboard');
    Route::get('/student/settings', fn () => $settingsPage(User::ROLE_STUDENT, 'student.dashboard'))
        ->middleware('role:'.User::ROLE_STUDENT)
        ->name('student.settings');

    Route::post('/student/settings/profile', $updateProfile)
        ->middleware('role:'.User::ROLE_STUDENT)
        ->name('student.settings.profile');

    Route::post('/student/settings/password', $updatePassword)
        ->middleware('role:'.User::ROLE_STUDENT)
        ->name('student.settings.password');

    Route::get('/student/notifications', function () {
        $user = Auth::user();
        $notificationService = app(AppNotificationService::class);

        return view('notifications.index', [
            'title' => 'Student Notifications',
            'dashboardRoute' => 'student.dashboard',
            'notificationsRoute' => 'student.notifications',
            'readRoute' => 'student.notifications.read',
            'readAllRoute' => 'student.notifications.read-all',
            'notifications' => $notificationService->visibleFor($user)->paginate(12),
            'unreadCount' => $notificationService->unreadCount($user),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.notifications');

    Route::post('/student/notifications/{notification}/read', function (AppNotification $notification) {
        app(AppNotificationService::class)->markAsRead($notification, Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.notifications.read');

    Route::post('/student/notifications/read-all', function () {
        app(AppNotificationService::class)->markAllAsRead(Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.notifications.read-all');

    Route::post('/opportunities/{opportunity}/apply', function (Opportunity $opportunity) {
        $user = Auth::user();

        abort_unless($user?->role === User::ROLE_STUDENT, 403);
        abort_unless($opportunity->status === Opportunity::STATUS_PUBLISHED, 404);

        $application = StudentApplication::firstOrCreate(
            [
                'opportunity_id' => $opportunity->id,
                'user_id' => $user->id,
            ],
            [
                'status' => StudentApplication::STATUS_DRAFT,
            ],
        );

        $opportunity->load(['requirements' => fn ($query) => $query->orderBy('sort_order')]);

        foreach ($opportunity->requirements as $requirement) {
            ApplicationDocument::firstOrCreate(
                [
                    'student_application_id' => $application->id,
                    'document_name' => $requirement->name,
                ],
                [
                    'status' => ApplicationDocument::STATUS_PENDING,
                ],
            );
        }

        return redirect()->route('student.applications.show', $application);
    })->middleware('role:'.User::ROLE_STUDENT)->name('opportunities.apply');

    Route::post('/subscriptions/{plan}/choose', function (SubscriptionPlan $plan) {
        $user = Auth::user();

        abort_unless($plan->status === SubscriptionPlan::STATUS_ACTIVE, 404);

        $existing = Subscription::query()
            ->with('payment')
            ->where('user_id', $user->id)
            ->where('subscription_plan_id', $plan->id)
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('student.subscriptions.show', $existing);
        }

        $payment = app(PaymentProviderManager::class)
            ->driver(Payment::PROVIDER_MANUAL)
            ->createPendingPayment([
                'user_id' => $user->id,
                'course_id' => null,
                'amount' => $plan->price_amount,
                'currency' => $plan->currency ?: 'RWF',
                'purpose' => Payment::PURPOSE_SUBSCRIPTION,
            ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        return redirect()->route('student.subscriptions.show', $subscription);
    })->middleware('role:'.User::ROLE_STUDENT)->name('subscriptions.choose');

    Route::get('/student/subscriptions', function () {
        $user = Auth::user();
        $status = request('status');

        $subscriptions = Subscription::query()
                ->with(['subscriptionPlan.courses.academy', 'payment'])
                ->where('user_id', $user->id)
            ->when($status === Subscription::STATUS_EXPIRED, fn ($query) => $query
                ->where(function ($expiredQuery): void {
                    $expiredQuery->where('status', Subscription::STATUS_EXPIRED)
                        ->orWhere(fn ($activeQuery) => $activeQuery
                            ->where('status', Subscription::STATUS_ACTIVE)
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<=', now()));
                }))
            ->when($status && $status !== Subscription::STATUS_EXPIRED, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('student.subscriptions', [
            'subscriptions' => $subscriptions,
            'activeStatus' => $status,
            'statuses' => [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_PENDING,
                Subscription::STATUS_EXPIRED,
                Subscription::STATUS_REJECTED,
                Subscription::STATUS_CANCELLED,
            ],
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.subscriptions');

    Route::get('/student/subscriptions/{subscription}', function (Subscription $subscription) {
        $user = Auth::user();

        abort_unless($subscription->user_id === $user->id, 403);

        return view('student.subscription-show', [
            'subscription' => $subscription->load(['subscriptionPlan.courses.academy', 'payment.paymentMethod']),
            'paymentMethods' => PaymentMethod::query()
                ->where('status', PaymentMethod::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.subscriptions.show');

    Route::post('/student/subscriptions/{subscription}/renew', function (Subscription $subscription) {
        $user = Auth::user();

        abort_unless($subscription->user_id === $user->id, 403);
        abort_unless(in_array($subscription->statusLabel(), [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED], true), 403);

        $subscription->load('subscriptionPlan');
        abort_unless($subscription->subscriptionPlan, 404);

        $pendingPayment = Payment::query()
            ->where('user_id', $user->id)
            ->where('purpose', Payment::PURPOSE_SUBSCRIPTION)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
            ->whereHas('subscription', fn ($query) => $query->whereKey($subscription->id))
            ->latest()
            ->first();

        if (! $pendingPayment) {
            $pendingPayment = app(PaymentProviderManager::class)
                ->driver(Payment::PROVIDER_MANUAL)
                ->createPendingPayment([
                    'user_id' => $user->id,
                    'course_id' => null,
                    'amount' => $subscription->subscriptionPlan->price_amount,
                    'currency' => $subscription->subscriptionPlan->currency ?: 'RWF',
                    'purpose' => Payment::PURPOSE_SUBSCRIPTION,
                ]);

            $subscription->update([
                'payment_id' => $pendingPayment->id,
                'status' => $subscription->isExpired() ? Subscription::STATUS_PENDING : $subscription->status,
            ]);
        }

        return redirect()->route('student.payments.show', $pendingPayment);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.subscriptions.renew');

    Route::get('/student/opportunities', function (Request $request) {
        $query = Opportunity::query()
            ->where('status', Opportunity::STATUS_PUBLISHED)
            ->orderByDesc('is_featured')
            ->orderBy('deadline');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->query('country'));
        }

        if ($request->query('deadline') === 'upcoming') {
            $query->whereNotNull('deadline')
                ->whereDate('deadline', '>=', now()->toDateString());
        }

        return view('student.opportunities', [
            'opportunities' => $query->get(),
            'types' => Opportunity::TYPES,
            'countries' => Opportunity::query()
                ->where('status', Opportunity::STATUS_PUBLISHED)
                ->whereNotNull('country')
                ->distinct()
                ->orderBy('country')
                ->pluck('country'),
            'filters' => $request->only(['type', 'country', 'deadline']),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.opportunities');

    Route::get('/student/documents', function () {
        $user = Auth::user();

        return view('student.documents', [
            'documents' => StudentDocument::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get(),
            'documentTypes' => StudentDocument::TYPES,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.documents');

    Route::post('/student/documents', function (Request $request) {
        $user = Auth::user();

        $validated = $request->validate([
            'document_type' => ['required', Rule::in(StudentDocument::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'document_file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg'],
        ]);

        StudentDocument::create([
            'user_id' => $user->id,
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $request->file('document_file')->store('student-documents', 'public'),
        ]);

        return redirect()->route('student.documents');
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.documents.store');

    Route::get('/student/documents/{document}/download', function (StudentDocument $document) {
        $user = Auth::user();

        abort_unless($document->user_id === $user->id, 403);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.documents.download');

    Route::delete('/student/documents/{document}', function (StudentDocument $document) {
        $user = Auth::user();

        abort_unless($document->user_id === $user->id, 403);

        if (! $document->applicationDocuments()->exists()) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('student.documents');
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.documents.destroy');

    Route::get('/student/applications', function () {
        $user = Auth::user();

        return view('student.applications', [
            'applications' => StudentApplication::query()
                ->with(['opportunity', 'documents'])
                ->where('user_id', $user->id)
                ->latest('updated_at')
                ->get(),
            'statuses' => StudentApplication::STATUSES,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.applications');

    Route::get('/student/applications/{application}', function (StudentApplication $application) {
        $user = Auth::user();

        abort_unless($application->user_id === $user->id, 403);

        $application->load([
            'opportunity.requirements' => fn ($query) => $query->orderBy('sort_order'),
            'documents.studentDocument',
            'statusHistories.changedBy',
        ]);

        foreach ($application->opportunity->requirements as $requirement) {
            ApplicationDocument::firstOrCreate(
                [
                    'student_application_id' => $application->id,
                    'document_name' => $requirement->name,
                ],
                [
                    'status' => ApplicationDocument::STATUS_PENDING,
                ],
            );
        }

        $application->refresh()->load([
            'opportunity.requirements' => fn ($query) => $query->orderBy('sort_order'),
            'documents.studentDocument',
            'statusHistories.changedBy',
        ]);

        return view('student.application-show', [
            'application' => $application,
            'studentDocuments' => StudentDocument::query()
                ->where('user_id', $user->id)
                ->latest()
                ->get(),
            'missingRequirements' => $application->opportunity->requirements
                ->filter(fn ($requirement): bool => $requirement->is_required
                    && ! $application->documents->contains(fn (ApplicationDocument $document): bool => $document->document_name === $requirement->name
                        && (filled($document->file_path) || filled($document->external_link))))
                ->values(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.applications.show');

    Route::post('/student/applications/{application}/documents', function (Request $request, StudentApplication $application) {
        $user = Auth::user();

        abort_unless($application->user_id === $user->id, 403);
        abort_if(in_array($application->status, [
            StudentApplication::STATUS_APPROVED,
            StudentApplication::STATUS_REJECTED,
            StudentApplication::STATUS_WITHDRAWN,
        ], true), 403);

        $validated = $request->validate([
            'application_document_id' => ['nullable', 'integer'],
            'student_document_id' => ['nullable', 'integer'],
            'document_name' => ['required', 'string', 'max:255'],
            'document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg'],
            'external_link' => ['nullable', 'url', 'max:255'],
        ]);

        $studentDocument = null;

        if (! empty($validated['student_document_id'])) {
            $studentDocument = StudentDocument::query()
                ->where('user_id', $user->id)
                ->whereKey($validated['student_document_id'])
                ->firstOrFail();
        }

        if (! $studentDocument && ! $request->hasFile('document_file') && blank($validated['external_link'] ?? null)) {
            return back()
                ->withErrors(['document' => 'Upload a file or add an external link before saving.'])
                ->withInput();
        }

        $document = null;

        if (! empty($validated['application_document_id'])) {
            $document = ApplicationDocument::query()
                ->where('student_application_id', $application->id)
                ->whereKey($validated['application_document_id'])
                ->first();
        }

        $document ??= new ApplicationDocument([
            'student_application_id' => $application->id,
        ]);

        $filePath = $studentDocument?->file_path ?? $document->file_path;

        if ($request->hasFile('document_file')) {
            $filePath = $request->file('document_file')->store('application-documents', 'public');
        }

        $document->fill([
            'student_document_id' => $studentDocument?->id,
            'document_name' => $validated['document_name'],
            'file_path' => $filePath,
            'external_link' => $validated['external_link'] ?? $document->external_link,
            'status' => ApplicationDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
        ])->save();

        return redirect()->route('student.applications.show', $application);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.applications.documents.store');

    Route::get('/student/applications/{application}/documents/{document}/download', function (StudentApplication $application, ApplicationDocument $document) {
        $user = Auth::user();

        abort_unless($application->user_id === $user->id, 403);
        abort_unless($document->student_application_id === $application->id, 403);
        abort_unless(filled($document->file_path) && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.applications.documents.download');

    Route::post('/student/applications/{application}/submit', function (StudentApplication $application) {
        $user = Auth::user();

        abort_unless($application->user_id === $user->id, 403);
        abort_unless($application->status === StudentApplication::STATUS_DRAFT, 403);

        $application->update([
            'status' => StudentApplication::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return redirect()->route('student.applications.show', $application);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.applications.submit');

    Route::post('/courses/{course}/enroll', function (Course $course) use ($activeSubscriptionForCourse) {
        $user = Auth::user();

        abort_unless($user?->role === User::ROLE_STUDENT, 403);
        abort_unless($course->status === Course::STATUS_PUBLISHED, 404);

        if ($course->requiresPayment()) {
            if ($activeSubscriptionForCourse($user, $course)) {
                Enrollment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'status' => Enrollment::STATUS_ACTIVE,
                        'enrolled_at' => now(),
                        'completed_at' => null,
                    ],
                );

                return redirect()->route('student.courses.learn', $course);
            }

            $approvedPayment = Payment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('purpose', Payment::PURPOSE_COURSE)
                ->where('status', Payment::STATUS_APPROVED)
                ->latest()
                ->first();

            if ($approvedPayment) {
                Enrollment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'status' => Enrollment::STATUS_ACTIVE,
                        'enrolled_at' => now(),
                        'completed_at' => null,
                    ],
                );

                return redirect()->route('student.courses.learn', $course);
            }

            $payment = Payment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('purpose', Payment::PURPOSE_COURSE)
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED, Payment::STATUS_REJECTED])
                ->latest()
                ->first();

            $payment ??= app(PaymentProviderManager::class)
                ->driver(Payment::PROVIDER_MANUAL)
                ->createPendingPayment([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'amount' => $course->payableAmount(),
                    'currency' => $course->currency ?: 'RWF',
                    'purpose' => Payment::PURPOSE_COURSE,
                ]);

            return redirect()->route('student.payments.show', $payment);
        }

        Enrollment::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
            ],
        );

        return redirect()->route('student.my-courses');
    })->middleware('role:'.User::ROLE_STUDENT)->name('courses.enroll');

    Route::get('/student/payments', function () {
        $user = Auth::user();

        return view('student.payments', [
            'payments' => Payment::query()
                ->with(['course.academy', 'paymentMethod', 'subscription.subscriptionPlan'])
                ->where('user_id', $user->id)
                ->latest()
                ->get(),
            'paymentMethods' => PaymentMethod::query()
                ->where('status', PaymentMethod::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.payments');

    Route::get('/student/payments/{payment}', function (Payment $payment) {
        $user = Auth::user();

        abort_unless($payment->user_id === $user->id, 403);

        return view('student.payment-show', [
            'payment' => $payment->load(['course.academy', 'paymentMethod', 'reviewer', 'subscription.subscriptionPlan.courses']),
            'paymentMethods' => PaymentMethod::query()
                ->where('status', PaymentMethod::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.payments.show');

    Route::post('/student/payments/{payment}', function (Request $request, Payment $payment) {
        $user = Auth::user();

        abort_unless($payment->user_id === $user->id, 403);
        abort_unless(in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED, Payment::STATUS_REJECTED], true), 403);

        $validated = $request->validate([
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('status', PaymentMethod::STATUS_ACTIVE)],
            'reference' => ['nullable', 'string', 'max:255'],
            'proof_file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg'],
        ]);

        $payment->update([
            'payment_method_id' => $validated['payment_method_id'],
            'reference' => $validated['reference'] ?? null,
            'proof_path' => $request->file('proof_file')->store('payment-proofs', 'public'),
            'status' => Payment::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'admin_notes' => null,
        ]);

        app(AppNotificationService::class)->createForRole(User::ROLE_ADMIN, [
            'title' => $payment->purpose === Payment::PURPOSE_SUBSCRIPTION ? 'Subscription payment proof submitted' : 'Payment proof submitted',
            'message' => $user->name.' submitted payment proof for '.($payment->purpose === Payment::PURPOSE_SUBSCRIPTION ? ($payment->subscription?->subscriptionPlan?->name ?? 'a subscription') : ($payment->course?->title ?? 'a course')).'.',
            'type' => AppNotification::TYPE_REMINDER,
            'category' => AppNotification::CATEGORY_PAYMENT,
            'action_url' => '/admin/payments',
            'created_by' => $user->id,
        ]);

        return redirect()->route('student.payments.show', $payment);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.payments.submit');

    Route::get('/student/my-courses', function () use ($courseProgress) {
        $user = Auth::user();
        $completionService = app(CourseCompletionService::class);

        $enrollments = Enrollment::query()
            ->with(['course.academy'])
            ->where('user_id', $user->id)
            ->latest('enrolled_at')
            ->get()
            ->filter(fn (Enrollment $enrollment): bool => (bool) $enrollment->course)
            ->map(fn (Enrollment $enrollment): array => [
                'enrollment' => $enrollment,
                'course' => $enrollment->course,
                'progress' => $courseProgress($user, $enrollment->course),
                'completion' => $completionService->calculate($user, $enrollment->course),
            ]);

        return view('student.my-courses', [
            'enrollments' => $enrollments,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.my-courses');

    Route::get('/student/courses/{course}/learn', function (Request $request, Course $course) use ($publishedLessonsForCourse, $courseProgress, $hasCourseAccess) {
        $user = Auth::user();

        abort_unless($course->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $course->load([
            'academy',
            'modules' => fn ($query) => $query
                ->where('status', Course::STATUS_PUBLISHED)
                ->orderBy('sort_order')
                ->with([
                    'lessons' => fn ($lessonQuery) => $lessonQuery
                        ->where('status', Course::STATUS_PUBLISHED)
                        ->orderBy('sort_order')
                        ->with([
                            'activities' => fn ($activityQuery) => $activityQuery
                                ->where('status', Course::STATUS_PUBLISHED)
                                ->orderBy('sort_order'),
                            'quizzes' => fn ($quizQuery) => $quizQuery
                                ->where('status', Quiz::STATUS_PUBLISHED)
                                ->withCount(['questions' => fn ($questionQuery) => $questionQuery->where('status', QuizQuestion::STATUS_PUBLISHED)])
                                ->with(['attempts' => fn ($attemptQuery) => $attemptQuery
                                    ->where('user_id', $user->id)
                                    ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED, QuizAttempt::STATUS_SUBMITTED])
                                    ->latest('submitted_at')])
                                ->orderBy('id'),
                            'assignments' => fn ($assignmentQuery) => $assignmentQuery
                                ->where('status', Assignment::STATUS_PUBLISHED)
                                ->orderBy('id')
                                ->with([
                                    'questions',
                                    'submissions' => fn ($submissionQuery) => $submissionQuery
                                        ->where('user_id', $user->id)
                                        ->latest(),
                                ]),
                        ]),
                ]),
        ]);

        $lessons = $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->values();
        $selectedLessonId = (int) $request->query('lesson');
        $currentLesson = $lessons->firstWhere('id', $selectedLessonId) ?? $lessons->first();
        $currentIndex = $currentLesson ? $lessons->search(fn (Lesson $lesson): bool => $lesson->id === $currentLesson->id) : false;
        $previousLesson = $currentIndex !== false && $currentIndex > 0 ? $lessons->get($currentIndex - 1) : null;
        $nextLesson = $currentIndex !== false ? $lessons->get($currentIndex + 1) : null;
        $upcomingActivities = $currentLesson
            ? $currentLesson->activities->take(3)
            : collect();
        $currentQuiz = $currentLesson
            ? $currentLesson->quizzes->first()
            : null;
        $currentAssignments = $currentLesson
            ? $currentLesson->assignments
            : collect();
        $currentLiveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'instructor'])
            ->whereIn('status', [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE, LiveClass::STATUS_COMPLETED])
            ->where(function ($query) use ($course, $currentLesson): void {
                $query->where('course_id', $course->id)
                    ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id))
                    ->orWhereHas('lesson.module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id));

                if ($currentLesson) {
                    $query->orWhere('lesson_id', $currentLesson->id);
                }
            })
            ->orderBy('starts_at')
            ->take(4)
            ->get();
        $completionService = app(CourseCompletionService::class);
        $completion = $completionService->calculate($user, $course);
        $studentReview = CourseReview::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $canReviewCourse = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->exists();

        return view('student.learn-course', [
            'course' => $course,
            'currentLesson' => $currentLesson,
            'currentQuiz' => $currentQuiz,
            'currentAssignments' => $currentAssignments,
            'currentLiveClasses' => $currentLiveClasses,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'upcomingActivities' => $upcomingActivities,
            'completedLessonIds' => LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->pluck('lesson_id')
                ->all(),
            'progress' => $courseProgress($user, $course),
            'completion' => $completion,
            'completionChecklist' => $completionService->checklist($user, $course, $completion),
            'studentReview' => $studentReview,
            'canReviewCourse' => $canReviewCourse,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.courses.learn');

    Route::post('/student/courses/{course}/reviews', function (Request $request, Course $course) {
        $user = Auth::user();

        abort_unless($course->status === Course::STATUS_PUBLISHED, 404);
        abort_unless(
            Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->exists(),
            403,
        );

        if (CourseReview::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            return back()->withErrors(['rating' => 'You have already submitted feedback for this course.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        CourseReview::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => CourseReview::STATUS_PENDING,
        ]);

        return redirect()
            ->route('student.courses.learn', $course)
            ->with('status', 'Thanks for your feedback. Your review is waiting for admin moderation.');
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.course-reviews.store');

    Route::post('/student/courses/{course}/lessons/{lesson}/complete', function (Course $course, Lesson $lesson) use ($hasCourseAccess) {
        $user = Auth::user();

        abort_unless($course->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_unless($lesson->status === Course::STATUS_PUBLISHED && $lesson->module?->course_id === $course->id, 404);

        LessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'course_id' => $course->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now(),
                'completed_at' => now(),
            ],
        );

        app(CourseCompletionService::class)->calculate($user, $course);

        return back();
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.lessons.complete');

    Route::get('/student/quizzes/{quiz}', function (Request $request, Quiz $quiz) use ($hasCourseAccess) {
        $user = Auth::user();

        $quiz->load([
            'lesson.module.course',
            'questions' => fn ($query) => $query
                ->where('status', QuizQuestion::STATUS_PUBLISHED)
                ->orderBy('sort_order')
                ->with(['options' => fn ($optionQuery) => $optionQuery->orderBy('sort_order')]),
        ]);

        $course = $quiz->lesson?->module?->course;

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $attemptCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED, QuizAttempt::STATUS_SUBMITTED])
            ->count();
        $attemptLimitReached = $quiz->max_attempts !== null && $attemptCount >= $quiz->max_attempts;
        $resultAttempt = null;

        if ($request->filled('attempt')) {
            $resultAttempt = QuizAttempt::query()
                ->with([
                    'answers.option',
                    'answers.question.options' => fn ($optionQuery) => $optionQuery->orderBy('sort_order'),
                ])
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->whereKey($request->integer('attempt'))
                ->first();
        }

        return view('student.quiz', [
            'quiz' => $quiz,
            'course' => $course,
            'attemptCount' => $attemptCount,
            'attemptLimitReached' => $attemptLimitReached,
            'resultAttempt' => $resultAttempt,
            'correctAnswerCount' => $resultAttempt?->answers->where('is_correct', true)->count() ?? 0,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.show');

    Route::post('/student/quizzes/{quiz}', function (Request $request, Quiz $quiz) use ($hasCourseAccess) {
        $user = Auth::user();

        $quiz->load([
            'lesson.module.course',
            'questions' => fn ($query) => $query
                ->where('status', QuizQuestion::STATUS_PUBLISHED)
                ->orderBy('sort_order')
                ->with(['options' => fn ($optionQuery) => $optionQuery->orderBy('sort_order')]),
        ]);

        $course = $quiz->lesson?->module?->course;

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $attemptCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED, QuizAttempt::STATUS_SUBMITTED])
            ->count();

        if ($quiz->max_attempts !== null && $attemptCount >= $quiz->max_attempts) {
            return back()->withErrors([
                'quiz' => 'You have reached the maximum number of attempts for this quiz.',
            ]);
        }

        if ($quiz->questions->isEmpty()) {
            return back()->withErrors([
                'quiz' => 'This quiz does not have published questions yet.',
            ]);
        }

        if ($quiz->questions->contains(fn ($question): bool => $question->options->isEmpty())) {
            return back()->withErrors([
                'answers' => 'This quiz is missing answer options. Please contact your instructor.',
            ]);
        }

        $rules = [
            'answers' => ['required', 'array'],
        ];

        foreach ($quiz->questions as $question) {
            $rules['answers.'.$question->id] = [
                'required',
                Rule::in($question->options->pluck('id')->map(fn ($id): string => (string) $id)->all()),
            ];
        }

        $submittedQuestionIds = collect(array_keys($request->input('answers', [])))->map(fn ($id): string => (string) $id);
        $allowedQuestionIds = $quiz->questions->pluck('id')->map(fn ($id): string => (string) $id);

        if ($submittedQuestionIds->diff($allowedQuestionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'One or more answers do not belong to this quiz.',
            ]);
        }

        $attributes = $quiz->questions
            ->mapWithKeys(fn ($question): array => ['answers.'.$question->id => 'quiz question'])
            ->all();

        $validated = $request->validate($rules, [], $attributes);

        $answers = $validated['answers'] ?? [];
        $totalPoints = (int) $quiz->questions->sum('points');
        $score = 0;

        $attempt = DB::transaction(function () use ($answers, $quiz, $user, $totalPoints, &$score): QuizAttempt {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'score' => 0,
                'total_points' => $totalPoints,
                'percentage' => 0,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            foreach ($quiz->questions as $question) {
                $selectedOptionId = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;
                $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                $isCorrect = (bool) $selectedOption?->is_correct;
                $pointsAwarded = $isCorrect ? (int) $question->points : 0;
                $score += $pointsAwarded;

                QuizAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'quiz_option_id' => $selectedOption?->id,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ]);
            }

            $percentage = $totalPoints > 0 ? (int) round(($score / $totalPoints) * 100) : 0;

            $attempt->update([
                'score' => $score,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'status' => $percentage >= $quiz->passing_score
                    ? QuizAttempt::STATUS_PASSED
                    : QuizAttempt::STATUS_FAILED,
                'submitted_at' => now(),
            ]);

            return $attempt;
        });

        return redirect()->route('student.quizzes.show', [
            'quiz' => $quiz,
            'attempt' => $attempt->id,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.submit');

    Route::get('/student/assignments', function () {
        $user = Auth::user();

        $enrollments = Enrollment::query()
            ->with([
                'course.modules.lessons.assignments' => fn ($query) => $query
                    ->where('status', Assignment::STATUS_PUBLISHED)
                    ->with(['submissions' => fn ($submissionQuery) => $submissionQuery
                        ->where('user_id', $user->id)
                        ->latest()]),
            ])
            ->where('user_id', $user->id)
            ->get();

        $assignments = $enrollments
            ->flatMap(fn (Enrollment $enrollment) => $enrollment->course?->modules ?? collect())
            ->flatMap(fn ($module) => $module->lessons)
            ->flatMap(fn (Lesson $lesson) => $lesson->assignments->map(fn (Assignment $assignment): array => [
                'assignment' => $assignment,
                'lesson' => $lesson,
                'submission' => $assignment->submissions->first(),
                'status' => $assignment->submissions->first()?->status ?? 'pending',
            ]))
            ->values();

        return view('student.assignments', [
            'assignments' => $assignments,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.assignments');

    Route::get('/student/assignments/{assignment}', function (Assignment $assignment) use ($hasCourseAccess) {
        $user = Auth::user();

        $assignment->load(['lesson.module.course', 'questions']);
        $course = $assignment->lesson?->module?->course;

        abort_unless($assignment->status === Assignment::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $submission = AssignmentSubmission::query()
            ->with(['questionAnswers.question'])
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return view('student.assignment', [
            'assignment' => $assignment,
            'course' => $course,
            'submission' => $submission,
            'canSubmit' => ! $submission || $submission->status === AssignmentSubmission::STATUS_RESUBMISSION_REQUIRED,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.assignments.show');

    Route::post('/student/assignments/{assignment}', function (Request $request, Assignment $assignment) use ($hasCourseAccess) {
        $user = Auth::user();

        $assignment->load(['lesson.module.course', 'questions']);
        $course = $assignment->lesson?->module?->course;

        abort_unless($assignment->status === Assignment::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $submission = AssignmentSubmission::query()
            ->with(['questionAnswers.question'])
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($submission && $submission->status !== AssignmentSubmission::STATUS_RESUBMISSION_REQUIRED) {
            return redirect()->route('student.assignments.show', $assignment)
                ->withErrors(['assignment' => 'You already have an active submission for this assignment.']);
        }

        $rules = [
            'text_answer' => ['nullable', 'string', 'max:20000'],
            'question_answers' => ['nullable', 'array'],
            'submission_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,zip,png,jpg,jpeg'],
            'external_link' => ['nullable', 'url', 'max:255'],
        ];

        if ($assignment->submission_type === Assignment::TYPE_TEXT) {
            $rules['text_answer'] = ['required', 'string', 'max:20000'];
        }

        if ($assignment->submission_type === Assignment::TYPE_FILE) {
            $rules['submission_file'] = ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,zip,png,jpg,jpeg'];
        }

        if ($assignment->submission_type === Assignment::TYPE_LINK) {
            $rules['external_link'] = ['required', 'url', 'max:255'];
        }

        foreach ($assignment->questions as $question) {
            $rules['question_answers.'.$question->id] = [
                $question->is_required ? 'required' : 'nullable',
                'string',
                'max:20000',
            ];
        }

        $submittedQuestionIds = collect(array_keys($request->input('question_answers', [])))->map(fn ($id): string => (string) $id);
        $allowedQuestionIds = $assignment->questions->pluck('id')->map(fn ($id): string => (string) $id);

        if ($submittedQuestionIds->diff($allowedQuestionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'question_answers' => 'One or more answers do not belong to this assignment.',
            ]);
        }

        $attributes = $assignment->questions
            ->mapWithKeys(fn ($question): array => ['question_answers.'.$question->id => 'assignment question'])
            ->all();

        $validated = $request->validate($rules, [], $attributes);
        $questionAnswers = collect($validated['question_answers'] ?? []);
        $hasQuestionAnswer = $assignment->questions
            ->contains(fn ($question): bool => filled($questionAnswers->get((string) $question->id, $questionAnswers->get($question->id))));

        if ($assignment->submission_type === Assignment::TYPE_MIXED
            && blank($validated['text_answer'] ?? null)
            && blank($validated['external_link'] ?? null)
            && ! $hasQuestionAnswer
            && ! $request->hasFile('submission_file')) {
            return back()
                ->withErrors(['submission' => 'Add a text answer, file, or external link before submitting.'])
                ->withInput();
        }

        $filePath = $submission?->file_path;

        if ($request->hasFile('submission_file')) {
            $filePath = $request->file('submission_file')->store('assignment-submissions', 'public');
        }

        DB::transaction(function () use ($assignment, $filePath, $questionAnswers, $user, $validated): void {
            $submission = AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                ],
                [
                    'text_answer' => $validated['text_answer'] ?? null,
                    'file_path' => $filePath,
                    'external_link' => $validated['external_link'] ?? null,
                    'score' => null,
                    'feedback' => null,
                    'status' => AssignmentSubmission::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                    'graded_at' => null,
                ],
            );

            $submission->questionAnswers()->delete();

            foreach ($assignment->questions as $question) {
                $answer = $questionAnswers->get((string) $question->id, $questionAnswers->get($question->id));

                if ($question->is_required || filled($answer)) {
                    AssignmentQuestionAnswer::create([
                        'assignment_submission_id' => $submission->id,
                        'assignment_question_id' => $question->id,
                        'answer' => $answer,
                    ]);
                }
            }
        });

        return redirect()->route('student.assignments.show', $assignment);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.assignments.submit');

    Route::get('/student/live-classes', function () {
        $user = Auth::user();

        $courseIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->pluck('course_id');

        $liveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'instructor', 'attendances' => fn ($query) => $query->where('user_id', $user->id)])
            ->where(function ($query) use ($courseIds): void {
                $query->whereIn('course_id', $courseIds)
                    ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->whereIn('course_id', $courseIds))
                    ->orWhereHas('lesson.module', fn ($moduleQuery) => $moduleQuery->whereIn('course_id', $courseIds));
            })
            ->where('status', '!=', LiveClass::STATUS_CANCELLED)
            ->orderBy('starts_at')
            ->get();

        return view('student.live-classes', [
            'liveClasses' => $liveClasses,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.live-classes');

    Route::post('/student/live-classes/{liveClass}/join', function (LiveClass $liveClass) use ($hasCourseAccess) {
        $user = Auth::user();

        $liveClass->load(['course', 'module.course', 'lesson.module.course']);
        $course = $liveClass->associatedCourse();

        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_if($liveClass->status === LiveClass::STATUS_CANCELLED, 404);

        LiveClassAttendance::updateOrCreate(
            [
                'live_class_id' => $liveClass->id,
                'user_id' => $user->id,
            ],
            [
                'status' => LiveClassAttendance::STATUS_ATTENDED,
                'joined_at' => LiveClassAttendance::query()
                    ->where('live_class_id', $liveClass->id)
                    ->where('user_id', $user->id)
                    ->value('joined_at') ?? now(),
            ],
        );

        return redirect()->away($liveClass->meeting_url);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.live-classes.join');

    Route::get('/student/mentorship', function () {
        $user = Auth::user();

        $assignments = MentorAssignment::query()
            ->with(['mentor', 'course', 'checkIns' => fn ($query) => $query->orderBy('scheduled_at')])
            ->where('student_id', $user->id)
            ->latest('assigned_at')
            ->get();

        return view('student.mentorship', [
            'assignments' => $assignments,
            'upcomingCheckIns' => $assignments
                ->flatMap(fn (MentorAssignment $assignment) => $assignment->checkIns)
                ->where('status', MentorCheckIn::STATUS_SCHEDULED)
                ->sortBy('scheduled_at')
                ->values(),
            'previousFeedback' => $assignments
                ->flatMap(fn (MentorAssignment $assignment) => $assignment->checkIns)
                ->where('status', MentorCheckIn::STATUS_COMPLETED)
                ->filter(fn (MentorCheckIn $checkIn): bool => filled($checkIn->mentor_feedback))
                ->sortByDesc('completed_at')
                ->values(),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.mentorship');

    Route::get('/student/certificates', function () {
        $user = Auth::user();

        $certificates = Certificate::query()
            ->with(['course', 'skills'])
            ->where('user_id', $user->id)
            ->latest('issued_at')
            ->get();

        return view('student.certificates', [
            'certificates' => $certificates,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.certificates');

    Route::get('/student/certificates/{certificate}', function (Certificate $certificate) {
        $user = Auth::user();

        abort_unless($certificate->user_id === $user->id, 403);

        $certificate->load(['skills', 'course']);

        return view('student.certificate-show', [
            'certificate' => $certificate,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.certificates.show');

    Route::get('/student/certificates/{certificate}/download', function (Certificate $certificate) {
        $user = Auth::user();

        abort_unless($certificate->user_id === $user->id, 403);
        abort_unless($certificate->status === Certificate::STATUS_ISSUED, 404);

        return app(CertificatePdfService::class)->download($certificate);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.certificates.download');

    Route::get('/admin/certificates/{certificate}/download-pdf', function (Certificate $certificate) {
        abort_unless($certificate->status === Certificate::STATUS_ISSUED, 404);

        return app(CertificatePdfService::class)->download($certificate);
    })->middleware('role:'.User::ROLE_ADMIN)->name('admin.certificates.download');

    $instructorCourseIds = function (User $instructor) {
        return LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course'])
            ->where('instructor_id', $instructor->id)
            ->get()
            ->map(fn (LiveClass $liveClass) => $liveClass->associatedCourse()?->id)
            ->filter()
            ->unique()
            ->values();
    };

    $instructorCoursesQuery = function (User $instructor) use ($instructorCourseIds) {
        return Course::query()
            ->with('academy')
            ->whereKey($instructorCourseIds($instructor));
    };

    $abortUnlessInstructorCourse = function (User $instructor, Course $course) use ($instructorCourseIds): void {
        abort_unless($instructorCourseIds($instructor)->contains($course->id), 403);
    };

    $instructorLiveClassesForCourse = function (User $instructor, Course $course) {
        return LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'attendances'])
            ->where('instructor_id', $instructor->id)
            ->where(function ($query) use ($course): void {
                $query->where('course_id', $course->id)
                    ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id))
                    ->orWhereHas('lesson.module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id));
            });
    };

    Route::get('/instructor/dashboard', function () use ($instructorCoursesQuery, $instructorCourseIds, $instructorLiveClassesForCourse) {
        $user = Auth::user();
        $courseIds = $instructorCourseIds($user);

        $pendingSubmissions = AssignmentSubmission::query()
            ->with(['user', 'assignment.lesson.module.course', 'questionAnswers.question'])
            ->where('status', AssignmentSubmission::STATUS_SUBMITTED)
            ->whereHas('assignment.lesson.module', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->latest('submitted_at');

        $quizAttempts = QuizAttempt::query()
            ->whereHas('quiz.lesson.module', fn ($query) => $query->whereIn('course_id', $courseIds));

        return view('instructor.dashboard', [
            'coursesCount' => $courseIds->count(),
            'studentsCount' => Enrollment::query()
                ->whereIn('course_id', $courseIds)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->distinct('user_id')
                ->count('user_id'),
            'pendingSubmissionsCount' => (clone $pendingSubmissions)->count(),
            'recentSubmissions' => (clone $pendingSubmissions)->take(5)->get(),
            'upcomingLiveClasses' => LiveClass::query()
                ->with(['course', 'module.course', 'lesson.module.course'])
                ->where('instructor_id', $user->id)
                ->whereIn('status', [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->take(5)
                ->get(),
            'quizAttemptsCount' => (clone $quizAttempts)->count(),
            'passedQuizAttemptsCount' => (clone $quizAttempts)->where('status', QuizAttempt::STATUS_PASSED)->count(),
            'courses' => $instructorCoursesQuery($user)
                ->withCount(['enrollments', 'modules'])
                ->orderBy('title')
                ->take(4)
                ->get()
                ->map(function (Course $course) use ($user, $instructorLiveClassesForCourse): Course {
                    $course->instructor_live_classes_count = $instructorLiveClassesForCourse($user, $course)->count();

                    return $course;
                }),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.dashboard');
    Route::get('/instructor/settings', fn () => $settingsPage(User::ROLE_INSTRUCTOR, 'instructor.dashboard'))
        ->middleware('role:'.User::ROLE_INSTRUCTOR)
        ->name('instructor.settings');

    Route::post('/instructor/settings/profile', $updateProfile)
        ->middleware('role:'.User::ROLE_INSTRUCTOR)
        ->name('instructor.settings.profile');

    Route::post('/instructor/settings/password', $updatePassword)
        ->middleware('role:'.User::ROLE_INSTRUCTOR)
        ->name('instructor.settings.password');

    Route::get('/instructor/courses', function () use ($instructorCoursesQuery, $instructorLiveClassesForCourse) {
        $user = Auth::user();

        return view('instructor.courses', [
            'courses' => $instructorCoursesQuery($user)
                ->withCount(['enrollments', 'modules'])
                ->orderBy('title')
                ->get()
                ->map(function (Course $course) use ($user, $instructorLiveClassesForCourse): Course {
                    $course->instructor_live_classes_count = $instructorLiveClassesForCourse($user, $course)->count();

                    return $course;
                }),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.index');

    Route::get('/instructor/courses/{course}', function (Course $course) use ($abortUnlessInstructorCourse, $instructorLiveClassesForCourse) {
        $user = Auth::user();

        $abortUnlessInstructorCourse($user, $course);

        $course->load('academy')->loadCount([
            'enrollments',
            'modules',
            'liveClasses',
            'modules as lessons_count' => fn ($query) => $query->join('lessons', 'lessons.module_id', '=', 'modules.id'),
        ]);

        return view('instructor.course-show', [
            'course' => $course,
            'assignmentsCount' => Assignment::query()
                ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->count(),
            'quizzesCount' => Quiz::query()
                ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->count(),
            'liveClassesCount' => $instructorLiveClassesForCourse($user, $course)->count(),
            'recentLiveClasses' => $instructorLiveClassesForCourse($user, $course)
                ->latest('starts_at')
                ->take(5)
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.show');

    Route::get('/instructor/courses/{course}/students', function (Course $course) use ($abortUnlessInstructorCourse, $courseProgress) {
        $abortUnlessInstructorCourse(Auth::user(), $course);

        $enrollments = Enrollment::query()
            ->with('user')
            ->where('course_id', $course->id)
            ->latest('enrolled_at')
            ->get()
            ->filter(fn (Enrollment $enrollment): bool => (bool) $enrollment->user)
            ->map(fn (Enrollment $enrollment): array => [
                'enrollment' => $enrollment,
                'student' => $enrollment->user,
                'progress' => $courseProgress($enrollment->user, $course),
            ]);

        return view('instructor.course-students', [
            'course' => $course->load('academy'),
            'enrollments' => $enrollments,
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.students');

    Route::get('/instructor/courses/{course}/submissions', function (Course $course) use ($abortUnlessInstructorCourse) {
        $abortUnlessInstructorCourse(Auth::user(), $course);

        return view('instructor.course-submissions', [
            'course' => $course->load('academy'),
            'submissions' => AssignmentSubmission::query()
                ->with(['user', 'assignment.lesson.module.course', 'questionAnswers.question'])
                ->whereHas('assignment.lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->latest('submitted_at')
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.submissions');

    Route::get('/instructor/courses/{course}/quiz-attempts', function (Course $course) use ($abortUnlessInstructorCourse) {
        $abortUnlessInstructorCourse(Auth::user(), $course);

        return view('instructor.course-quiz-attempts', [
            'course' => $course->load('academy'),
            'attempts' => QuizAttempt::query()
                ->with(['user', 'quiz.lesson.module.course'])
                ->whereHas('quiz.lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->latest('submitted_at')
                ->get(),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.quiz-attempts');

    Route::get('/instructor/notifications', function () {
        $user = Auth::user();
        $notificationService = app(AppNotificationService::class);

        return view('notifications.index', [
            'title' => 'Instructor Notifications',
            'dashboardRoute' => 'instructor.dashboard',
            'notificationsRoute' => 'instructor.notifications',
            'readRoute' => 'instructor.notifications.read',
            'readAllRoute' => 'instructor.notifications.read-all',
            'notifications' => $notificationService->visibleFor($user)->paginate(12),
            'unreadCount' => $notificationService->unreadCount($user),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.notifications');

    Route::post('/instructor/notifications/{notification}/read', function (AppNotification $notification) {
        app(AppNotificationService::class)->markAsRead($notification, Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.notifications.read');

    Route::post('/instructor/notifications/read-all', function () {
        app(AppNotificationService::class)->markAllAsRead(Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.notifications.read-all');

    Route::get('/instructor/live-classes', function () {
        $user = Auth::user();

        $liveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'attendances.user'])
            ->where('instructor_id', $user->id)
            ->orderBy('starts_at')
            ->get();

        return view('instructor.live-classes', [
            'liveClasses' => $liveClasses,
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.index');

    Route::get('/mentor/dashboard', function () {
        $user = Auth::user();

        $assignments = MentorAssignment::query()
            ->with(['student', 'course', 'checkIns'])
            ->where('mentor_id', $user->id)
            ->where('status', MentorAssignment::STATUS_ACTIVE)
            ->latest('assigned_at')
            ->get();

        return view('mentor.dashboard', [
            'assignments' => $assignments,
            'upcomingCheckIns' => $assignments
                ->flatMap(fn (MentorAssignment $assignment) => $assignment->checkIns)
                ->where('status', MentorCheckIn::STATUS_SCHEDULED)
                ->sortBy('scheduled_at')
                ->values(),
        ]);
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.dashboard');
    Route::get('/mentor/settings', fn () => $settingsPage(User::ROLE_MENTOR, 'mentor.dashboard'))
        ->middleware('role:'.User::ROLE_MENTOR)
        ->name('mentor.settings');

    Route::post('/mentor/settings/profile', $updateProfile)
        ->middleware('role:'.User::ROLE_MENTOR)
        ->name('mentor.settings.profile');

    Route::post('/mentor/settings/password', $updatePassword)
        ->middleware('role:'.User::ROLE_MENTOR)
        ->name('mentor.settings.password');

    Route::get('/mentor/notifications', function () {
        $user = Auth::user();
        $notificationService = app(AppNotificationService::class);

        return view('notifications.index', [
            'title' => 'Mentor Notifications',
            'dashboardRoute' => 'mentor.dashboard',
            'notificationsRoute' => 'mentor.notifications',
            'readRoute' => 'mentor.notifications.read',
            'readAllRoute' => 'mentor.notifications.read-all',
            'notifications' => $notificationService->visibleFor($user)->paginate(12),
            'unreadCount' => $notificationService->unreadCount($user),
        ]);
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.notifications');

    Route::post('/mentor/notifications/{notification}/read', function (AppNotification $notification) {
        app(AppNotificationService::class)->markAsRead($notification, Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.notifications.read');

    Route::post('/mentor/notifications/read-all', function () {
        app(AppNotificationService::class)->markAllAsRead(Auth::user());

        return back();
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.notifications.read-all');

    Route::get('/mentor/students', function () {
        $user = Auth::user();

        $assignments = MentorAssignment::query()
            ->with(['student', 'course', 'checkIns' => fn ($query) => $query->latest('scheduled_at')])
            ->where('mentor_id', $user->id)
            ->latest('assigned_at')
            ->get();

        return view('mentor.students', [
            'assignments' => $assignments,
        ]);
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.students');

    Route::get('/mentor/check-ins', function () {
        $user = Auth::user();

        $checkIns = MentorCheckIn::query()
            ->with(['mentorAssignment.student', 'mentorAssignment.course'])
            ->whereHas('mentorAssignment', fn ($query) => $query->where('mentor_id', $user->id))
            ->orderByRaw('scheduled_at is null')
            ->orderBy('scheduled_at')
            ->get();

        return view('mentor.check-ins', [
            'checkIns' => $checkIns,
        ]);
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.check-ins');

    Route::post('/mentor/check-ins/{checkIn}/complete', function (Request $request, MentorCheckIn $checkIn) {
        $user = Auth::user();

        $checkIn->load('mentorAssignment');

        abort_unless($checkIn->mentorAssignment?->mentor_id === $user->id, 403);

        $validated = $request->validate([
            'mentor_feedback' => ['required', 'string', 'max:10000'],
        ]);

        $checkIn->update([
            'mentor_feedback' => $validated['mentor_feedback'],
            'status' => MentorCheckIn::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return redirect()->route('mentor.check-ins');
    })->middleware('role:'.User::ROLE_MENTOR)->name('mentor.check-ins.complete');
});














