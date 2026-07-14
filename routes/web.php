<?php

use App\Models\User;
use App\Models\Academy;
use App\Models\AppNotification;
use App\Models\Assignment;
use App\Models\AssignmentQuestionAnswer;
use App\Models\AssignmentOption;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentQuestion;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\EntranceExamInstitution;
use App\Models\EntranceExamPastPaper;
use App\Models\EntranceExamProgram;
use App\Models\EntranceExamSubject;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\LessonProgress;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\MentorAssignment;
use App\Models\MentorCheckIn;
use App\Models\Module;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\StudentDocument;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CourseCompletionService;
use App\Services\AppNotificationService;
use App\Services\AdminCsvExportService;
use App\Services\CertificatePdfService;
use App\Services\QuizAttemptService;
use App\Services\Payments\PaymentProviderManager;
use App\Support\CourseContentRenderer;
use App\Support\LoginAuthenticator;
use App\Rules\YouTubeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

Route::get('/', function () use ($publicAcademies, $publicCourses) {
    $studentCount = 0;

    try {
        $studentCount = Schema::hasTable('users')
            ? User::query()->where('role', User::ROLE_STUDENT)->count()
            : 0;
    } catch (Throwable) {
        $studentCount = 0;
    }

    return view('pages.home', [
        'academies' => $publicAcademies(),
        'courses' => $publicCourses(),
        'studentCount' => $studentCount,
    ]);
})->name('home');

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
            'reviews' => [],
        ]);
})->name('courses.show');


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
        ->with(['skills', 'course.instructor'])
        ->where('verification_code', $verification_code)
        ->first();

    return view('pages.certificate-verify', [
        'certificate' => $certificate,
        'isValid' => $certificate?->status === Certificate::STATUS_ISSUED,
        'verificationStatus' => $certificate?->status,
    ]);
})->name('certificates.verify');

Route::get('/entrance-exam-academy', function (Request $request) {
    if (! Schema::hasTable('entrance_exam_past_papers')) {
        return view('pages.entrance-exam-academy.index', [
            'papers' => new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 12),
            'institutions' => collect(),
            'programs' => collect(),
            'subjects' => collect(),
            'years' => collect(),
            'examTypes' => collect(),
            'filters' => $request->only(['q', 'institution', 'program', 'subject', 'year', 'exam_type']),
        ]);
    }

    $filters = $request->validate([
        'q' => ['nullable', 'string', 'max:120'],
        'institution' => ['nullable', 'integer'],
        'program' => ['nullable', 'integer'],
        'subject' => ['nullable', 'integer'],
        'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        'exam_type' => ['nullable', 'string', 'max:120'],
    ]);

    $papers = EntranceExamPastPaper::query()
        ->with(['institution', 'program', 'subject'])
        ->where('status', EntranceExamPastPaper::STATUS_PUBLISHED)
        ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(function ($paperQuery) use ($term): void {
            $paperQuery->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%')
                ->orWhere('exam_type', 'like', '%'.$term.'%');
        }))
        ->when($filters['institution'] ?? null, fn ($query, $institutionId) => $query->where('entrance_exam_institution_id', $institutionId))
        ->when($filters['program'] ?? null, fn ($query, $programId) => $query->where('entrance_exam_program_id', $programId))
        ->when($filters['subject'] ?? null, fn ($query, $subjectId) => $query->where('entrance_exam_subject_id', $subjectId))
        ->when($filters['year'] ?? null, fn ($query, $year) => $query->where('exam_year', $year))
        ->when($filters['exam_type'] ?? null, fn ($query, $type) => $query->where('exam_type', $type))
        ->orderByDesc('is_featured')
        ->orderByDesc('exam_year')
        ->orderBy('title')
        ->paginate(12)
        ->withQueryString();

    return view('pages.entrance-exam-academy.index', [
        'papers' => $papers,
        'institutions' => EntranceExamInstitution::query()->where('status', EntranceExamInstitution::STATUS_ACTIVE)->orderBy('name')->get(),
        'programs' => EntranceExamProgram::query()->where('status', EntranceExamProgram::STATUS_ACTIVE)->orderBy('name')->get(),
        'subjects' => EntranceExamSubject::query()->where('status', EntranceExamSubject::STATUS_ACTIVE)->orderBy('name')->get(),
        'years' => EntranceExamPastPaper::query()->where('status', EntranceExamPastPaper::STATUS_PUBLISHED)->whereNotNull('exam_year')->distinct()->orderByDesc('exam_year')->pluck('exam_year'),
        'examTypes' => EntranceExamPastPaper::query()->where('status', EntranceExamPastPaper::STATUS_PUBLISHED)->whereNotNull('exam_type')->distinct()->orderBy('exam_type')->pluck('exam_type'),
        'filters' => $filters,
    ]);
})->name('entrance-exam-academy.index');

Route::get('/entrance-exam-academy/papers/{paper:slug}', function (EntranceExamPastPaper $paper) {
    abort_unless($paper->isPublished(), 404);

    $paper->load(['institution', 'program', 'subject']);

    return view('pages.entrance-exam-academy.show', [
        'paper' => $paper,
    ]);
})->name('entrance-exam-academy.papers.show');

Route::get('/entrance-exam-academy/papers/{paper:slug}/view', function (EntranceExamPastPaper $paper) {
    abort_unless($paper->isPublished(), 404);

    $paper->load(['institution', 'program', 'subject']);

    return view('pages.entrance-exam-academy.viewer', [
        'paper' => $paper,
        'watermark' => Auth::user()?->email ?: 'MK Scholars',
    ]);
})->middleware('auth')->name('entrance-exam-academy.papers.view');

Route::get('/entrance-exam-academy/papers/{paper:slug}/inline', function (EntranceExamPastPaper $paper) {
    abort_unless($paper->isPublished(), 404);
    abort_unless($paper->hasPdfFile(), 404);

    $disk = Storage::disk($paper->paperFileDisk());
    abort_unless($disk->exists($paper->paper_file_path), 404);

    return response()->file($disk->path($paper->paper_file_path), [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.Str::slug($paper->title ?: 'entrance-exam-paper').'.pdf"',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->middleware('auth')->name('entrance-exam-academy.papers.inline');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', function (Request $request, LoginAuthenticator $authenticator) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = $authenticator->attempt($credentials, $request->boolean('remember'));

        return redirect($user->dashboardPath());
    })->name('login.store');
    Route::view('/register', 'auth.register')->name('register');
});

Route::view('/setup-admin', 'auth.setup-admin')->name('setup-admin');


Route::get('/notifications', function () {
    $user = Auth::user();

    abort_unless($user, 403);

    return match ($user->role) {
        User::ROLE_ADMIN => redirect('/admin/app-notifications'),
        User::ROLE_INSTRUCTOR => redirect()->route('instructor.notifications'),
        User::ROLE_MENTOR => redirect()->route('mentor.notifications'),
        default => redirect()->route('student.notifications'),
    };
})->middleware('auth')->name('notifications.redirect');
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

Route::middleware('auth')
    ->prefix('admin/reports/exports')
    ->name('admin.reports.exports.')
    ->group(function (): void {
        $canViewReports = function (Request $request): void {
            $user = $request->user();

            abort_unless(
                $user?->role === User::ROLE_ADMIN
                    || ($user?->role === User::ROLE_VIEWER && $user->hasViewerPermission(User::VIEWER_PERMISSION_REPORTS)),
                403
            );
        };

        Route::get('/students', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->students($request->query());
        })->name('students');
        Route::get('/enrollments', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->enrollments($request->query());
        })->name('enrollments');
        Route::get('/payments', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->payments($request->query());
        })->name('payments');
        Route::get('/subscriptions', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->subscriptions($request->query());
        })->name('subscriptions');
        Route::get('/certificates', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->certificates($request->query());
        })->name('certificates');
        Route::get('/quiz-attempts', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->quizAttempts($request->query());
        })->name('quiz-attempts');
        Route::get('/assignment-submissions', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->assignmentSubmissions($request->query());
        })->name('assignment-submissions');
        Route::get('/course-reviews', function (Request $request) use ($canViewReports) {
            $canViewReports($request);

            return app(AdminCsvExportService::class)->courseReviews($request->query());
        })->name('course-reviews');
    });

Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])
    ->prefix('admin/account-settings')
    ->name('admin.account-settings.')
    ->group(function (): void {
        Route::post('/profile', function (Request $request) {
            $user = $request->user();

            abort_unless($user->isApproved(), 403);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'string', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user)],
            ]);

            $user->update($validated);

            return back()->with('profile_status', 'Your admin profile has been updated.');
        })->name('profile');

        Route::post('/password', function (Request $request) {
            abort_unless($request->user()->isApproved(), 403);

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

            return back()->with('password_status', 'Your admin password has been updated.');
        })->name('password');
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

$deleteInstructorSignature = function (?string $path): void {
    if (filled($path) && Str::startsWith($path, 'certificates/instructor-signatures/')) {
        Storage::disk('public')->delete($path);
    }
};

$updateInstructorSignature = function (Request $request) use ($deleteInstructorSignature) {
    $validated = $request->validate([
        'signature' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
    ]);

    $user = $request->user();
    $oldPath = $user->signature_path;
    $path = $validated['signature']->store('certificates/instructor-signatures', 'public');

    $user->update([
        'signature_path' => $path,
    ]);

    if ($oldPath !== $path) {
        $deleteInstructorSignature($oldPath);
    }

    return back()->with('signature_status', 'Your certificate signature has been updated.');
};

$removeInstructorSignature = function (Request $request) use ($deleteInstructorSignature) {
    $user = $request->user();
    $oldPath = $user->signature_path;

    $user->update([
        'signature_path' => null,
    ]);

    $deleteInstructorSignature($oldPath);

    return back()->with('signature_status', 'Your certificate signature has been removed.');
};

Route::middleware('auth')->group(function () use ($publishedLessonsForCourse, $courseProgress, $activeSubscriptionForCourse, $hasCourseAccess, $settingsPage, $updateProfile, $updatePassword, $updateInstructorSignature, $removeInstructorSignature): void {
    Route::get('/student/dashboard', function () use ($courseProgress) {
        $user = Auth::user();
        $notificationService = app(AppNotificationService::class);
        $completionService = app(CourseCompletionService::class);

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
            'enrolledCourses' => Enrollment::query()
                ->with('course.academy')
                ->where('user_id', $user->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->latest('enrolled_at')
                ->take(3)
                ->get()
                ->filter(fn (Enrollment $enrollment): bool => (bool) $enrollment->course)
                ->map(function (Enrollment $enrollment) use ($user, $courseProgress, $completionService): array {
                    $completion = $completionService->calculate($user, $enrollment->course);

                    return [
                        'course' => $enrollment->course,
                        'progress' => $courseProgress($user, $enrollment->course),
                        'completion' => $completion,
                    ];
                })
                ->values(),
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
            'proof_file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg'],
        ]);

        $payment->update([
            'payment_method_id' => $validated['payment_method_id'],
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

    Route::get('/student/my-courses', function () use ($courseProgress, $hasCourseAccess) {
        $user = Auth::user();
        $completionService = app(CourseCompletionService::class);

        $enrollments = Enrollment::query()
            ->with(['course.academy', 'course.instructor'])
            ->where('user_id', $user->id)
            ->latest('enrolled_at')
            ->get()
            ->filter(fn (Enrollment $enrollment): bool => (bool) $enrollment->course && $enrollment->course->status === Course::STATUS_PUBLISHED);

        $activeCourses = $enrollments
            ->filter(fn (Enrollment $enrollment): bool => $hasCourseAccess($user, $enrollment->course))
            ->map(function (Enrollment $enrollment) use ($user, $courseProgress, $completionService): array {
                $course = $enrollment->course;
                $completion = $completionService->calculate($user, $course);
                $certificate = Certificate::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->latest()
                    ->first();

                return [
                    'enrollment' => $enrollment,
                    'course' => $course,
                    'progress' => $courseProgress($user, $course),
                    'completion' => $completion,
                    'certificate' => $certificate,
                    'access_label' => $course->isFree() ? 'Active' : 'Paid',
                ];
            });

        $activeSubscriptions = Subscription::query()
            ->with(['subscriptionPlan.courses.academy', 'subscriptionPlan.courses.instructor'])
            ->where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->get();

        $activeSubscriptionCourses = $activeSubscriptions
            ->flatMap(fn (Subscription $subscription) => $subscription->subscriptionPlan?->courses
                ? $subscription->subscriptionPlan->courses
                    ->filter(fn (Course $course): bool => $course->status === Course::STATUS_PUBLISHED)
                    ->map(function (Course $course) use ($subscription, $user, $courseProgress, $completionService): array {
                        $completion = $completionService->calculate($user, $course);
                        $certificate = Certificate::query()
                            ->where('user_id', $user->id)
                            ->where('course_id', $course->id)
                            ->latest()
                            ->first();

                        return [
                            'enrollment' => null,
                            'course' => $course,
                            'progress' => $courseProgress($user, $course),
                            'completion' => $completion,
                            'certificate' => $certificate,
                            'access_label' => $subscription->subscriptionPlan?->name ?? 'Active subscription',
                        ];
                    })
                : collect())
            ->reject(fn (array $item): bool => $activeCourses->contains(fn (array $active): bool => $active['course']->is($item['course'])));

        $activeCourses = $activeCourses
            ->concat($activeSubscriptionCourses)
            ->unique(fn (array $item): int => $item['course']->id)
            ->values();

        $coursePayments = Payment::query()
            ->with(['course.academy', 'course.instructor'])
            ->where('user_id', $user->id)
            ->where('purpose', Payment::PURPOSE_COURSE)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED, Payment::STATUS_REJECTED])
            ->latest()
            ->get()
            ->filter(fn (Payment $payment): bool => (bool) $payment->course && $payment->course->status === Course::STATUS_PUBLISHED && ! $hasCourseAccess($user, $payment->course))
            ->map(fn (Payment $payment): array => [
                'course' => $payment->course,
                'payment' => $payment,
                'subscription' => null,
                'status_label' => match ($payment->status) {
                    Payment::STATUS_SUBMITTED => 'Pending Payment',
                    Payment::STATUS_REJECTED => 'Payment Rejected',
                    default => 'Unpaid',
                },
                'status_tone' => $payment->status === Payment::STATUS_REJECTED ? 'danger' : 'warning',
                'reason' => match ($payment->status) {
                    Payment::STATUS_SUBMITTED => 'Payment proof is awaiting admin review.',
                    Payment::STATUS_REJECTED => 'The previous proof was rejected. Upload a new proof to continue.',
                    default => 'Upload payment proof to unlock course access.',
                },
                'pay_label' => match ($payment->status) {
                    Payment::STATUS_SUBMITTED => 'Payment Pending',
                    Payment::STATUS_REJECTED => 'Pay Again',
                    default => 'Pay Now',
                },
                'pay_href' => route('student.payments.show', $payment),
                'pay_form_route' => null,
            ]);

        $blockedEnrollmentCourses = $enrollments
            ->reject(fn (Enrollment $enrollment): bool => $hasCourseAccess($user, $enrollment->course))
            ->reject(fn (Enrollment $enrollment): bool => $coursePayments->contains(fn (array $item): bool => $item['course']->is($enrollment->course)))
            ->map(fn (Enrollment $enrollment): array => [
                'course' => $enrollment->course,
                'payment' => null,
                'subscription' => null,
                'status_label' => $enrollment->status === Enrollment::STATUS_CANCELLED ? 'Not Enrolled' : 'Unpaid',
                'status_tone' => 'warning',
                'reason' => $enrollment->course->requiresPayment()
                    ? 'Course access is blocked until payment is submitted and approved.'
                    : 'Enrollment is not active.',
                'pay_label' => $enrollment->course->requiresPayment() ? 'Pay Now' : 'View Details',
                'pay_href' => null,
                'pay_form_route' => $enrollment->course->requiresPayment() ? route('courses.enroll', $enrollment->course) : null,
            ]);

        $subscriptionIssues = Subscription::query()
            ->with(['subscriptionPlan.courses.academy', 'subscriptionPlan.courses.instructor', 'payment'])
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_REJECTED, Subscription::STATUS_EXPIRED])
                    ->orWhere(fn ($activeQuery) => $activeQuery
                        ->where('status', Subscription::STATUS_ACTIVE)
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<=', now()));
            })
            ->latest()
            ->get()
            ->flatMap(fn (Subscription $subscription) => $subscription->subscriptionPlan?->courses
                ? $subscription->subscriptionPlan->courses
                    ->filter(fn (Course $course): bool => $course->status === Course::STATUS_PUBLISHED && ! $hasCourseAccess($user, $course))
                    ->map(function (Course $course) use ($subscription): array {
                        $label = $subscription->isExpired() ? 'Expired' : ($subscription->status === Subscription::STATUS_REJECTED ? 'Payment Rejected' : 'Pending Payment');
                        $payment = $subscription->payment;

                        return [
                            'course' => $course,
                            'payment' => $payment,
                            'subscription' => $subscription,
                            'status_label' => $label,
                            'status_tone' => $subscription->status === Subscription::STATUS_REJECTED ? 'danger' : 'warning',
                            'reason' => match ($label) {
                                'Expired' => 'Your subscription for this course has expired. Renew to restore access.',
                                'Payment Rejected' => 'The subscription payment was rejected. Upload a new proof to continue.',
                                default => 'Subscription payment is awaiting proof or admin review.',
                            },
                            'pay_label' => match ($label) {
                                'Expired' => 'Renew Plan',
                                'Payment Rejected' => 'Pay Again',
                                default => 'Payment Pending',
                            },
                            'pay_href' => $payment ? route('student.payments.show', $payment) : route('student.subscriptions.show', $subscription),
                            'pay_form_route' => $label === 'Expired' ? route('student.subscriptions.renew', $subscription) : null,
                        ];
                    })
                : collect());

        $unpaidCourses = $coursePayments
            ->concat($blockedEnrollmentCourses)
            ->concat($subscriptionIssues)
            ->unique(fn (array $item): int => $item['course']->id)
            ->values();

        $enrollments = $activeCourses->map(fn (array $item): array => [
                'enrollment' => $item['enrollment'],
                'course' => $item['course'],
                'progress' => $item['progress'],
                'completion' => $item['completion'],
            ]);

        return view('student.my-courses', [
            'enrollments' => $enrollments,
            'activeCourses' => $activeCourses,
            'unpaidCourses' => $unpaidCourses,
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

        // Opening a lesson records that learning started; completion stays an explicit student action.
        if ($currentLesson) {
            $lessonProgress = LessonProgress::firstOrNew([
                'user_id' => $user->id,
                'lesson_id' => $currentLesson->id,
            ]);

            if (! $lessonProgress->exists) {
                $lessonProgress->fill([
                    'course_id' => $course->id,
                    'status' => LessonProgress::STATUS_IN_PROGRESS,
                    'progress_percent' => 10,
                    'started_at' => now(),
                ])->save();
            }
        }

        $upcomingActivities = $currentLesson
            ? $currentLesson->activities->take(3)
            : collect();
        $currentQuiz = $currentLesson
            ? $currentLesson->quizzes->first()
            : null;
        $finalTest = Quiz::query()
            ->where('course_id', $course->id)
            ->where('quiz_type', Quiz::TYPE_FINAL_TEST)
            ->where('status', Quiz::STATUS_PUBLISHED)
            ->withCount(['questions' => fn ($questionQuery) => $questionQuery->where('status', QuizQuestion::STATUS_PUBLISHED)])
            ->with(['attempts' => fn ($attemptQuery) => $attemptQuery
                ->where('user_id', $user->id)
                ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED, QuizAttempt::STATUS_SUBMITTED])
                ->latest('submitted_at')])
            ->latest()
            ->first();
        $currentAssignments = $currentLesson
            ? $currentLesson->assignments
            : collect();
        $currentLiveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'instructor'])
            ->whereIn('status', [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE, LiveClass::STATUS_COMPLETED, LiveClass::STATUS_CANCELLED])
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
            'finalTest' => $finalTest,
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

    Route::get('/student/lesson-materials/{activity}/view', function (LessonActivity $activity) use ($hasCourseAccess) {
        $user = Auth::user();

        $activity->load('lesson.module.course');
        $course = $activity->lesson?->module?->course;

        abort_unless($activity->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_unless($activity->hasUploadedResource(), 404);
        abort_unless($activity->isPdfResource(), 404);

        $disk = Storage::disk($activity->resourceDisk());
        abort_unless($disk->exists($activity->resource_path), 404);

        $path = $disk->path($activity->resource_path);
        $filename = Str::slug($activity->title ?: 'lesson-notes').'.pdf';

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.lesson-materials.view');

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
        $quizAttemptService = app(QuizAttemptService::class);

        $quiz->load([
            'lesson.module.course',
            'questions' => fn ($query) => $query
                ->where('status', QuizQuestion::STATUS_PUBLISHED)
                ->orderBy('sort_order')
                ->with(['options' => fn ($optionQuery) => $optionQuery->orderBy('sort_order')]),
        ]);

        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $attemptCount = $quizAttemptService->completedAttemptCount($user, $quiz);
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
                ->whereNotNull('submitted_at')
                ->whereKey($request->integer('attempt'))
                ->first();
        }

        if ($resultAttempt) {
            return view('student.quiz-result', [
                'quiz' => $quiz,
                'course' => $course,
                'attempt' => $resultAttempt,
                'attemptCount' => $attemptCount,
                'attemptLimitReached' => $attemptLimitReached,
                'correctAnswerCount' => $resultAttempt->answers->where('is_correct', true)->count(),
            ]);
        }

        return view('student.quiz-instructions', [
            'quiz' => $quiz,
            'course' => $course,
            'activeAttempt' => $quizAttemptService->activeAttempt($user, $quiz),
            'attemptCount' => $attemptCount,
            'attemptLimitReached' => $attemptLimitReached,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.show');

    Route::post('/student/quizzes/{quiz}/start', function (Quiz $quiz) use ($hasCourseAccess) {
        $user = Auth::user();

        $quiz->load('lesson.module.course');
        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $attempt = app(QuizAttemptService::class)->startOrResume($user, $quiz);

        return redirect()->route('student.quizzes.question', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questionIndex' => $attempt->current_question_index ?? 0,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.start');

    Route::get('/student/quizzes/{quiz}/attempts/{attempt}/questions/{questionIndex?}', function (Quiz $quiz, QuizAttempt $attempt, int $questionIndex = 0) use ($hasCourseAccess) {
        $user = Auth::user();
        $quizAttemptService = app(QuizAttemptService::class);

        $quiz->load('lesson.module.course');
        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === $user->id, 403);

        if ($attempt->submitted_at || $attempt->status !== QuizAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]);
        }

        if ($quizAttemptService->isExpired($attempt)) {
            $quizAttemptService->submit($attempt, $quiz);

            return redirect()
                ->route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id])
                ->with('status', 'Time expired. Your saved answers were submitted.');
        }

        $questions = $quizAttemptService->publishedQuestions($quiz)->values();
        abort_if($questions->isEmpty(), 404);

        $questionIndex = max(0, min($questionIndex, $questions->count() - 1));
        $question = $questions->get($questionIndex);
        $savedAnswer = QuizAnswer::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->where('quiz_question_id', $question->id)
            ->first();

        return view('student.quiz-question', [
            'quiz' => $quiz,
            'course' => $course,
            'attempt' => $attempt,
            'questions' => $questions,
            'question' => $question,
            'questionIndex' => $questionIndex,
            'savedAnswer' => $savedAnswer,
            'secondsRemaining' => $quizAttemptService->secondsRemaining($attempt),
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.question');

    Route::post('/student/quizzes/{quiz}/attempts/{attempt}/questions/{questionIndex}', function (Request $request, Quiz $quiz, QuizAttempt $attempt, int $questionIndex) use ($hasCourseAccess) {
        $user = Auth::user();
        $quizAttemptService = app(QuizAttemptService::class);

        $quiz->load('lesson.module.course');
        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === $user->id, 403);

        $validated = $request->validate([
            'option_id' => ['nullable', 'integer'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer'],
            'answer_text' => ['nullable', 'string', 'max:20000'],
        ]);

        $questions = $quizAttemptService->publishedQuestions($quiz)->values();
        $question = $questions->get($questionIndex);
        $selectedOptionIds = $question?->acceptsMultipleOptions()
            ? ($validated['option_ids'] ?? (isset($validated['option_id']) ? [(int) $validated['option_id']] : []))
            : (int) ($validated['option_id'] ?? 0);
        $quizAttemptService->saveAnswer($attempt, $quiz, $questionIndex, $selectedOptionIds, $validated['answer_text'] ?? null);

        if ($request->boolean('finish') || $questionIndex >= $questions->count() - 1) {
            $quizAttemptService->submit($attempt, $quiz);

            return redirect()->route('student.quizzes.show', [
                'quiz' => $quiz,
                'attempt' => $attempt->id,
            ]);
        }

        return redirect()
            ->route('student.quizzes.question', [
                'quiz' => $quiz,
                'attempt' => $attempt,
                'questionIndex' => $questionIndex + 1,
            ])
            ->with('status', 'Answer saved. Loading next question...');
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.answer');

    Route::post('/student/quizzes/{quiz}/attempts/{attempt}/submit', function (Quiz $quiz, QuizAttempt $attempt) use ($hasCourseAccess) {
        $user = Auth::user();
        $quizAttemptService = app(QuizAttemptService::class);

        $quiz->load('lesson.module.course');
        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === $user->id, 403);

        $quizAttemptService->submit($attempt, $quiz);

        return redirect()->route('student.quizzes.show', [
            'quiz' => $quiz,
            'attempt' => $attempt->id,
        ]);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.quizzes.finish');

    Route::post('/student/quizzes/{quiz}', function (Request $request, Quiz $quiz) use ($hasCourseAccess) {
        $user = Auth::user();
        $quizAttemptService = app(QuizAttemptService::class);

        $quiz->load('lesson.module.course');
        $course = $quiz->courseContext();

        abort_unless($quiz->status === Quiz::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $attempt = $quizAttemptService->startOrResume($user, $quiz);
        $questions = $quizAttemptService->publishedQuestions($quiz)->values();
        $answers = $request->input('answers', []);
        $submittedQuestionIds = collect(array_keys($answers))->map(fn ($id): string => (string) $id);
        $allowedQuestionIds = $questions->pluck('id')->map(fn ($id): string => (string) $id);

        if ($submittedQuestionIds->diff($allowedQuestionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'One or more answers do not belong to this quiz.',
            ]);
        }

        foreach ($questions as $index => $question) {
            if (! array_key_exists($question->id, $answers)) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'Please answer this quiz question.',
                ]);
            }

            try {
                $quizAttemptService->saveAnswer($attempt, $quiz, $index, $answers[$question->id]);
            } catch (ValidationException $exception) {
                if (array_key_exists('option_id', $exception->errors()) || array_key_exists('option_ids', $exception->errors())) {
                    throw ValidationException::withMessages([
                        'answers.'.$question->id => 'The selected answer does not belong to this question.',
                    ]);
                }

                throw $exception;
            }
        }

        $quizAttemptService->submit($attempt, $quiz);

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

        $assignment->load(['lesson.module.course', 'questions.options']);
        $course = $assignment->lesson?->module?->course;

        abort_unless($assignment->status === Assignment::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $submission = AssignmentSubmission::query()
            ->with(['questionAnswers.question.options'])
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

        $assignment->load(['lesson.module.course', 'questions.options']);
        $course = $assignment->lesson?->module?->course;

        abort_unless($assignment->status === Assignment::STATUS_PUBLISHED, 404);
        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        abort_unless($hasCourseAccess($user, $course), 403);

        $submission = AssignmentSubmission::query()
            ->with(['questionAnswers.question.options'])
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
            if ($question->acceptsMultipleOptions()) {
                $rules['question_answers.'.$question->id] = [
                    $question->is_required ? 'required' : 'nullable',
                    'array',
                ];
                $rules['question_answers.'.$question->id.'.*'] = ['integer'];
            } elseif ($question->acceptsSingleOption()) {
                $rules['question_answers.'.$question->id] = [
                    $question->is_required ? 'required' : 'nullable',
                    'integer',
                ];
            } else {
                $rules['question_answers.'.$question->id] = [
                    $question->is_required ? 'required' : 'nullable',
                    'string',
                    'max:20000',
                ];
            }
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

        foreach ($assignment->questions as $question) {
            if (! $question->requiresOptions()) {
                continue;
            }

            $rawAnswer = $questionAnswers->get((string) $question->id, $questionAnswers->get($question->id));
            $submittedOptionIds = collect(is_array($rawAnswer) ? $rawAnswer : (filled($rawAnswer) ? [$rawAnswer] : []))
                ->map(fn ($optionId): int => (int) $optionId)
                ->filter()
                ->values();

            if ($submittedOptionIds->diff($question->options->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'question_answers.'.$question->id => 'Choose an option that belongs to this assignment question.',
                ]);
            }
        }

        $hasQuestionAnswer = $assignment->questions
            ->contains(function ($question) use ($questionAnswers): bool {
                $answer = $questionAnswers->get((string) $question->id, $questionAnswers->get($question->id));

                return is_array($answer)
                    ? collect($answer)->filter(fn ($value): bool => filled($value))->isNotEmpty()
                    : filled($answer);
            });

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
                $isObjectiveQuestion = $question->requiresOptions();
                $selectedOptionIds = collect($isObjectiveQuestion ? (is_array($answer) ? $answer : (filled($answer) ? [$answer] : [])) : [])
                    ->map(fn ($optionId): int => (int) $optionId)
                    ->filter()
                    ->values();
                $hasAnswer = $isObjectiveQuestion
                    ? $selectedOptionIds->isNotEmpty()
                    : filled($answer);

                if ($question->is_required || $hasAnswer) {
                    $selectedOptions = $isObjectiveQuestion
                        ? $question->options->whereIn('id', $selectedOptionIds)
                        : collect();

                    AssignmentQuestionAnswer::create([
                        'assignment_submission_id' => $submission->id,
                        'assignment_question_id' => $question->id,
                        'answer' => $isObjectiveQuestion ? $selectedOptions->pluck('option_text')->implode(', ') : $answer,
                        'selected_option_ids' => $isObjectiveQuestion ? $selectedOptionIds->all() : null,
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
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->pluck('course_id');

        $liveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'instructor', 'attendances' => fn ($query) => $query->where('user_id', $user->id)])
            ->where(function ($query) use ($courseIds): void {
                $query->whereIn('course_id', $courseIds)
                    ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->whereIn('course_id', $courseIds))
                    ->orWhereHas('lesson.module', fn ($moduleQuery) => $moduleQuery->whereIn('course_id', $courseIds));
            })
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
        if (! $hasCourseAccess($user, $course)) {
            return back()->withErrors(['live_class' => 'You do not have access to this class.']);
        }

        if ($liveClass->isUpcoming()) {
            return back()->withErrors(['live_class' => 'Class has not started yet.']);
        }

        if ($liveClass->isEnded()) {
            return back()->withErrors(['live_class' => 'Class has ended.']);
        }

        if ($liveClass->status === LiveClass::STATUS_CANCELLED) {
            return back()->withErrors(['live_class' => 'This class has been cancelled.']);
        }

        if (! filled($liveClass->meeting_url)) {
            return back()->withErrors(['live_class' => 'Meeting link is not available.']);
        }

        if (! $liveClass->canJoin()) {
            return back()->withErrors(['live_class' => 'Class is not available to join right now.']);
        }

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

    Route::get('/student/live-classes/{liveClass}/recording', function (LiveClass $liveClass) use ($hasCourseAccess) {
        $user = Auth::user();

        $liveClass->load(['course', 'module.course', 'lesson.module.course']);
        $course = $liveClass->associatedCourse();

        abort_unless($course?->status === Course::STATUS_PUBLISHED, 404);
        if (! $hasCourseAccess($user, $course)) {
            return back()->withErrors(['live_class' => 'You do not have access to this class.']);
        }

        if ($liveClass->status === LiveClass::STATUS_CANCELLED) {
            return back()->withErrors(['live_class' => 'This class has been cancelled.']);
        }

        if (! $liveClass->canWatchRecording()) {
            return back()->withErrors(['live_class' => 'Recording is not available yet.']);
        }

        return redirect()->away($liveClass->recording_url);
    })->middleware('role:'.User::ROLE_STUDENT)->name('student.live-classes.recording');

    Route::get('/student/mentorship', function () {
        abort_unless(config('mkscholars.features.mentorship_enabled', false), 404);

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

        $certificate->load(['skills', 'course.instructor']);

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
        $ownedCourseIds = Course::query()
            ->where('instructor_id', $instructor->id)
            ->pluck('id');

        $liveClassCourseIds = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course'])
            ->where('instructor_id', $instructor->id)
            ->get()
            ->map(fn (LiveClass $liveClass) => $liveClass->associatedCourse()?->id);

        return $ownedCourseIds
            ->merge($liveClassCourseIds)
            ->filter()
            ->unique()
            ->values();
    };

    $instructorCoursesQuery = function (User $instructor) use ($instructorCourseIds) {
        return Course::query()
            ->with(['academy', 'instructor'])
            ->whereKey($instructorCourseIds($instructor));
    };

    $abortUnlessInstructorCourse = function (User $instructor, Course $course) use ($instructorCourseIds): void {
        abort_unless($instructorCourseIds($instructor)->contains($course->id), 403);
    };

    $abortUnlessInstructorOwnsCourse = function (User $instructor, Course $course): void {
        abort_unless($course->ownedBy($instructor), 403);
    };


    $uniqueSlug = function (string $table, string $title, ?string $requestedSlug = null, ?int $ignoreId = null): string {
        $base = Str::slug(filled($requestedSlug) ? $requestedSlug : $title) ?: 'item';
        $slug = $base;
        $counter = 2;

        while (DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    };
    $lessonBelongsToInstructorCourse = function (Course $course, int $lessonId): Lesson {
        $lesson = Lesson::query()
            ->whereKey($lessonId)
            ->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->first();

        abort_unless($lesson, 422, 'The selected lesson does not belong to this course.');

        return $lesson;
    };

    $quizBelongsToInstructorCourse = function (User $instructor, Quiz $quiz) use ($abortUnlessInstructorOwnsCourse): Quiz {
        $quiz->loadMissing(['course', 'lesson.module.course']);
        $course = $quiz->courseContext();

        abort_unless($course instanceof Course, 404);
        $abortUnlessInstructorOwnsCourse($instructor, $course);

        return $quiz;
    };

    $storeInstructorQuizQuestion = function (Quiz $quiz, array $data, bool $questionRequired = true): ?QuizQuestion {
        if (! filled($data['question_text'] ?? null)) {
            if ($questionRequired) {
                throw ValidationException::withMessages([
                    'question_text' => 'Add a question before saving.',
                ]);
            }

            return null;
        }

        $questionType = $data['question_type'] ?? QuizQuestion::TYPE_SINGLE_CHOICE;
        $requiresOptions = in_array($questionType, [
            QuizQuestion::TYPE_SINGLE_CHOICE,
            QuizQuestion::TYPE_MULTIPLE_CHOICE,
            QuizQuestion::TYPE_TRUE_FALSE,
        ], true);
        $rawOptions = collect($data['options'] ?? [])
            ->map(fn ($option, int|string $index): array => [
                'index' => (int) $index,
                'text' => trim((string) ($option['option_text'] ?? '')),
            ])
            ->filter(fn (array $option): bool => filled($option['text']))
            ->values();

        if ($questionType === QuizQuestion::TYPE_TRUE_FALSE) {
            $rawOptions = collect([
                ['index' => 0, 'text' => 'True'],
                ['index' => 1, 'text' => 'False'],
            ]);
        }

        if ($requiresOptions && $rawOptions->count() < 2) {
            throw ValidationException::withMessages([
                'options' => 'Add at least two options for this question.',
            ]);
        }

        $validCorrectIndexes = collect();

        if ($requiresOptions) {
            $correctIndexes = $questionType === QuizQuestion::TYPE_MULTIPLE_CHOICE
                ? collect($data['correct_option_indexes'] ?? [])->map(fn ($index): int => (int) $index)->unique()->values()
                : collect([(int) ($data['correct_option_index'] ?? -1)]);
            $availableIndexes = $rawOptions->pluck('index');
            $validCorrectIndexes = $correctIndexes->intersect($availableIndexes)->values();

            if ($questionType === QuizQuestion::TYPE_MULTIPLE_CHOICE && $validCorrectIndexes->isEmpty()) {
                throw ValidationException::withMessages([
                    'correct_option_indexes' => 'Choose at least one correct answer.',
                ]);
            }

            if ($questionType !== QuizQuestion::TYPE_MULTIPLE_CHOICE && $validCorrectIndexes->count() !== 1) {
                throw ValidationException::withMessages([
                    'correct_option_index' => 'Choose exactly one correct answer.',
                ]);
            }
        } else {
            $rawOptions = collect();
        }

        $question = $quiz->questions()->create([
            'question_text' => $data['question_text'],
            'question_type' => $questionType,
            'points' => $data['points'] ?? 1,
            'sort_order' => $data['sort_order'] ?? (($quiz->questions()->max('sort_order') ?? 0) + 1),
            'status' => $data['question_status'] ?? QuizQuestion::STATUS_PUBLISHED,
        ]);

        foreach ($rawOptions as $position => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $validCorrectIndexes->contains($option['index']),
                'sort_order' => $position + 1,
            ]);
        }

        return $question;
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

    $abortUnlessInstructorLiveClass = function (User $instructor, LiveClass $liveClass) use ($abortUnlessInstructorCourse): Course {
        $liveClass->loadMissing(['course', 'module.course', 'lesson.module.course']);

        abort_unless((int) $liveClass->instructor_id === (int) $instructor->id, 403);

        $course = $liveClass->associatedCourse();
        abort_unless($course instanceof Course, 404);
        $abortUnlessInstructorCourse($instructor, $course);

        return $course;
    };

    $validateInstructorLiveClass = function (Request $request, User $instructor) use ($instructorCourseIds): array {
        $courseIds = $instructorCourseIds($instructor)->all();

        return $request->validate([
            'course_id' => ['required', 'integer', Rule::in($courseIds)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'meeting_url' => ['required', 'url', 'max:255'],
            'platform' => ['required', Rule::in(LiveClass::PLATFORMS)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(LiveClass::STATUSES)],
            'recording_url' => ['nullable', 'url', 'max:255'],
        ]);
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

        // Average lesson-completion per course (used for the metric row + per-course cards).
        $completionByCourse = \App\Models\CourseCompletion::query()
            ->selectRaw('course_id, AVG(lesson_percentage) as avg_lessons')
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->pluck('avg_lessons', 'course_id');

        return view('instructor.dashboard', [
            'coursesCount' => $courseIds->count(),
            'publishedCoursesCount' => Course::query()->whereKey($courseIds)->where('status', Course::STATUS_PUBLISHED)->count(),
            'draftCoursesCount' => Course::query()->whereKey($courseIds)->where('status', Course::STATUS_DRAFT)->count(),
            'avgCompletionRate' => $completionByCourse->isNotEmpty() ? (int) round($completionByCourse->avg()) : 0,
            'studentsCount' => Enrollment::query()
                ->whereIn('course_id', $courseIds)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->distinct('user_id')
                ->count('user_id'),
            'pendingSubmissionsCount' => (clone $pendingSubmissions)->count(),
            'recentSubmissions' => (clone $pendingSubmissions)->take(5)->get(),
            'newestEnrollments' => Enrollment::query()
                ->with(['user', 'course'])
                ->whereIn('course_id', $courseIds)
                ->latest('enrolled_at')
                ->take(5)
                ->get(),
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
                ->take(6)
                ->get()
                ->map(function (Course $course) use ($user, $instructorLiveClassesForCourse, $completionByCourse): Course {
                    $course->instructor_live_classes_count = $instructorLiveClassesForCourse($user, $course)->count();
                    $course->completion_percentage = (int) round($completionByCourse[$course->id] ?? 0);

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

    Route::post('/instructor/settings/signature', $updateInstructorSignature)
        ->middleware('role:'.User::ROLE_INSTRUCTOR)
        ->name('instructor.settings.signature');

    Route::post('/instructor/settings/signature/remove', $removeInstructorSignature)
        ->middleware('role:'.User::ROLE_INSTRUCTOR)
        ->name('instructor.settings.signature.remove');

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


    Route::get('/instructor/courses/create', function () {
        return view('instructor.course-form', [
            'course' => new Course([
                'access_type' => Course::ACCESS_FREE,
                'is_free' => true,
                'offers_certificate' => false,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ]),
            'academies' => Academy::query()->orderBy('name')->get(),
            'modules' => collect(),
            'lessons' => collect(),
            'quizzes' => collect(),
            'finalTest' => null,
            'assignments' => collect(),
            'mode' => 'create',
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.create');

    Route::post('/instructor/courses', function (Request $request) use ($uniqueSlug) {
        $validated = $request->validate([
            'academy_id' => ['required', 'exists:academies,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('courses', 'slug')],
            'short_description' => ['required', 'string', 'max:600'],
            'full_description' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'level' => ['nullable', 'string', 'max:80'],
            'duration' => ['nullable', 'string', 'max:80'],
            'access_type' => ['required', Rule::in([Course::ACCESS_FREE, Course::ACCESS_PAID])],
            'offers_certificate' => ['nullable', 'boolean'],
            'price_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(Course::STATUSES)],
            'learning_outcomes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image_path'] = $request->file('featured_image')->store('courses', 'public');
        }

        unset($validated['featured_image']);

        $validated['slug'] = $uniqueSlug('courses', $validated['title'], $validated['slug'] ?? null);
        $validated['instructor_id'] = Auth::id();
        $validated['is_free'] = $validated['access_type'] === Course::ACCESS_FREE;
        $validated['offers_certificate'] = (bool) ($validated['offers_certificate'] ?? false);
        $validated['currency'] = $validated['currency'] ?: 'RWF';
        $validated['learning_outcomes'] = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['learning_outcomes'] ?? '')))
            ->map(fn ($outcome) => trim($outcome))
            ->filter()
            ->values()
            ->all();

        $course = Course::create($validated);

        return redirect()->route('instructor.courses.edit', $course)->with('status', 'Course draft created. You can now add modules, lessons, quizzes, and assignments.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.store');

    Route::get('/instructor/courses/{course}/edit', function (Course $course) use ($abortUnlessInstructorOwnsCourse) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $course->load(['academy', 'modules.lessons.activities', 'modules.lessons.quizzes.questions.options', 'modules.lessons.assignments.questions.options']);

        return view('instructor.course-form', [
            'course' => $course,
            'academies' => Academy::query()->orderBy('name')->get(),
            'modules' => $course->modules()->with(['lessons.activities' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])->orderBy('sort_order')->orderBy('title')->get(),
            'lessons' => Lesson::query()->with(['activities' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])->whereHas('module', fn ($query) => $query->where('course_id', $course->id))->orderBy('sort_order')->orderBy('title')->get(),
            'quizzes' => Quiz::query()
                ->with(['lesson.module', 'questions.options'])
                ->where('quiz_type', Quiz::TYPE_LESSON_QUIZ)
                ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
                ->latest()
                ->get(),
            'finalTest' => Quiz::query()
                ->with(['questions.options', 'attempts'])
                ->where('course_id', $course->id)
                ->where('quiz_type', Quiz::TYPE_FINAL_TEST)
                ->latest()
                ->first(),
            'assignments' => Assignment::query()->with(['lesson.module', 'questions.options'])->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))->latest()->get(),
            'mode' => 'edit',
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.edit');

    Route::put('/instructor/courses/{course}', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $uniqueSlug) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'academy_id' => ['required', 'exists:academies,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('courses', 'slug')->ignore($course)],
            'short_description' => ['required', 'string', 'max:600'],
            'full_description' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'level' => ['nullable', 'string', 'max:80'],
            'duration' => ['nullable', 'string', 'max:80'],
            'access_type' => ['required', Rule::in([Course::ACCESS_FREE, Course::ACCESS_PAID])],
            'offers_certificate' => ['nullable', 'boolean'],
            'price_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(Course::STATUSES)],
            'learning_outcomes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('featured_image')) {
            if ($course->featured_image_path) {
                Storage::disk('public')->delete($course->featured_image_path);
            }

            $validated['featured_image_path'] = $request->file('featured_image')->store('courses', 'public');
        }

        unset($validated['featured_image']);

        $validated['slug'] = $uniqueSlug('courses', $validated['title'], $validated['slug'] ?? null, $course->id);
        $validated['is_free'] = $validated['access_type'] === Course::ACCESS_FREE;
        $validated['offers_certificate'] = (bool) ($validated['offers_certificate'] ?? false);
        $validated['currency'] = $validated['currency'] ?: 'RWF';
        $validated['learning_outcomes'] = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['learning_outcomes'] ?? '')))
            ->map(fn ($outcome) => trim($outcome))
            ->filter()
            ->values()
            ->all();

        $course->update($validated);

        return back()->with('status', 'Course details updated.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.courses.update');

    Route::post('/instructor/courses/{course}/modules', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $uniqueSlug) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'summary' => ['nullable', 'string', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(Course::STATUSES)],
        ]);

        $course->modules()->create([
            'title' => $validated['title'],
            'slug' => $uniqueSlug('modules', $validated['title'], $validated['slug'] ?? null),
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Module added.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.modules.store');

    Route::post('/instructor/courses/{course}/lessons', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $uniqueSlug) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'summary' => ['nullable', 'string', 'max:600'],
            'lesson_type' => ['required', Rule::in(['video', 'text', 'quiz', 'assignment', 'live'])],
            'video_url' => ['nullable', 'url', new YouTubeUrl()],
            'content' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Course::STATUSES)],
        ]);

        $module = Module::query()->where('course_id', $course->id)->whereKey($validated['module_id'])->first();
        abort_unless($module, 422, 'The selected module does not belong to this course.');

        $module->lessons()->create([
            'title' => $validated['title'],
            'slug' => $uniqueSlug('lessons', $validated['title'], $validated['slug'] ?? null),
            'summary' => $validated['summary'] ?? null,
            'lesson_type' => $validated['lesson_type'],
            'video_url' => $validated['video_url'] ?? null,
            'content' => $validated['content'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_free_preview' => (bool) ($validated['is_free_preview'] ?? false),
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Lesson added.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.lessons.store');

    Route::post('/instructor/courses/{course}/lesson-materials', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'material_file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,webp,doc,docx,ppt,pptx'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(Course::STATUSES)],
        ]);

        $lesson = Lesson::query()
            ->whereKey($validated['lesson_id'])
            ->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->first();

        abort_unless($lesson, 422, 'The selected lesson does not belong to this course.');

        $file = $request->file('material_file');
        $path = $file->store('lesson-materials', 'public');

        $lesson->activities()->create([
            'activity_type' => 'download',
            'type' => 'material',
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'resource_path' => $path,
            'resource_disk' => 'public',
            'resource_mime' => $file->getMimeType(),
            'sort_order' => $validated['sort_order'] ?? (($lesson->activities()->max('sort_order') ?? 0) + 1),
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Lesson material uploaded.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.lesson-materials.store');

    Route::post('/instructor/courses/{course}/quizzes', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $lessonBelongsToInstructorCourse, $storeInstructorQuizQuestion) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(Quiz::STATUSES)],
            'publish_quiz' => ['nullable', 'boolean'],
            'question_text' => ['nullable', 'string', 'max:1200'],
            'question_type' => ['nullable', Rule::in(QuizQuestion::TYPES)],
            'points' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'question_status' => ['nullable', Rule::in([QuizQuestion::STATUS_DRAFT, QuizQuestion::STATUS_PUBLISHED])],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['nullable', 'string', 'max:500'],
            'correct_option_index' => ['nullable', 'integer', 'min:0'],
            'correct_option_indexes' => ['nullable', 'array'],
            'correct_option_indexes.*' => ['integer', 'min:0'],
        ]);

        $lesson = $lessonBelongsToInstructorCourse($course, (int) $validated['lesson_id']);

        $quiz = $lesson->quizzes()->create([
            'course_id' => $course->id,
            'quiz_type' => Quiz::TYPE_LESSON_QUIZ,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'passing_score' => $validated['passing_score'],
            'max_attempts' => $validated['max_attempts'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'status' => $request->boolean('publish_quiz') ? Quiz::STATUS_PUBLISHED : $validated['status'],
        ]);

        $storeInstructorQuizQuestion($quiz, $validated, false);

        return back()->with('status', 'Quiz added.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.quizzes.store');

    Route::post('/instructor/courses/{course}/final-test', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $storeInstructorQuizQuestion) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(Quiz::STATUSES)],
            'publish_quiz' => ['nullable', 'boolean'],
            'question_text' => ['nullable', 'string', 'max:1200'],
            'question_type' => ['nullable', Rule::in(QuizQuestion::TYPES)],
            'points' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'question_status' => ['nullable', Rule::in([QuizQuestion::STATUS_DRAFT, QuizQuestion::STATUS_PUBLISHED])],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['nullable', 'string', 'max:500'],
            'correct_option_index' => ['nullable', 'integer', 'min:0'],
            'correct_option_indexes' => ['nullable', 'array'],
            'correct_option_indexes.*' => ['integer', 'min:0'],
        ]);

        $finalTest = Quiz::query()
            ->where('course_id', $course->id)
            ->where('quiz_type', Quiz::TYPE_FINAL_TEST)
            ->first();

        abort_if($finalTest, 422, 'This course already has a final test.');

        $finalTest = $course->finalTest()->create([
            'lesson_id' => null,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'passing_score' => $validated['passing_score'],
            'max_attempts' => $validated['max_attempts'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'status' => $request->boolean('publish_quiz') ? Quiz::STATUS_PUBLISHED : $validated['status'],
        ]);

        $storeInstructorQuizQuestion($finalTest, $validated, false);

        return back()->with('status', 'Final Test added.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.final-tests.store');

    Route::post('/instructor/quizzes/{quiz}/questions', function (Request $request, Quiz $quiz) use ($quizBelongsToInstructorCourse, $storeInstructorQuizQuestion) {
        $quizBelongsToInstructorCourse(Auth::user(), $quiz);

        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:1200'],
            'question_type' => ['required', Rule::in(QuizQuestion::TYPES)],
            'points' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'question_status' => ['required', Rule::in([QuizQuestion::STATUS_DRAFT, QuizQuestion::STATUS_PUBLISHED])],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['nullable', 'string', 'max:500'],
            'correct_option_index' => ['nullable', 'integer', 'min:0'],
            'correct_option_indexes' => ['nullable', 'array'],
            'correct_option_indexes.*' => ['integer', 'min:0'],
        ]);

        $storeInstructorQuizQuestion($quiz, $validated);

        return back()->with('status', 'Question saved.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.quizzes.questions.store');

    Route::post('/instructor/courses/{course}/assignments', function (Request $request, Course $course) use ($abortUnlessInstructorOwnsCourse, $lessonBelongsToInstructorCourse) {
        $abortUnlessInstructorOwnsCourse(Auth::user(), $course);

        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'instruction_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,txt,zip,png,jpg,jpeg'],
            'submission_type' => ['required', Rule::in([Assignment::TYPE_TEXT, Assignment::TYPE_FILE, Assignment::TYPE_LINK, Assignment::TYPE_MIXED])],
            'max_score' => ['required', 'integer', 'min:1'],
            'due_days_after_enrollment' => ['nullable', 'integer', 'min:0'],
            'allow_late_submission' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([Assignment::STATUS_DRAFT, Assignment::STATUS_PUBLISHED, Assignment::STATUS_ARCHIVED])],
            'question_text' => ['nullable', 'string', 'max:1200'],
            'question_type' => ['nullable', Rule::in(AssignmentQuestion::TYPES)],
            'question_points' => ['nullable', 'integer', 'min:0'],
            'question_required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['nullable', 'string', 'max:500'],
            'correct_option_index' => ['nullable', 'integer', 'min:0'],
            'correct_option_indexes' => ['nullable', 'array'],
            'correct_option_indexes.*' => ['integer', 'min:0'],
        ]);

        $lesson = $lessonBelongsToInstructorCourse($course, (int) $validated['lesson_id']);
        $instructionFilePath = $request->hasFile('instruction_file')
            ? $request->file('instruction_file')->store('assignment-instructions', 'public')
            : null;

        $assignment = $lesson->assignments()->create([
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'instruction_file_path' => $instructionFilePath,
            'submission_type' => $validated['submission_type'],
            'max_score' => $validated['max_score'],
            'due_days_after_enrollment' => $validated['due_days_after_enrollment'] ?? null,
            'allow_late_submission' => (bool) ($validated['allow_late_submission'] ?? true),
            'status' => $validated['status'],
        ]);

        if (filled($validated['question_text'] ?? null)) {
            $questionType = $validated['question_type'] ?? AssignmentQuestion::TYPE_TEXTAREA;
            $question = $assignment->questions()->create([
                'question_text' => $validated['question_text'],
                'question_type' => $questionType,
                'points' => $validated['question_points'] ?? $validated['max_score'],
                'sort_order' => 1,
                'is_required' => (bool) ($validated['question_required'] ?? true),
            ]);

            $rawOptions = collect($validated['options'] ?? [])
                ->map(fn ($option, int|string $index): array => [
                    'index' => (int) $index,
                    'text' => trim((string) ($option['option_text'] ?? '')),
                ])
                ->filter(fn (array $option): bool => filled($option['text']))
                ->values();

            if ($questionType === AssignmentQuestion::TYPE_TRUE_FALSE) {
                $rawOptions = collect([
                    ['index' => 0, 'text' => 'True'],
                    ['index' => 1, 'text' => 'False'],
                ]);
            }

            if (in_array($questionType, [AssignmentQuestion::TYPE_SINGLE_CHOICE, AssignmentQuestion::TYPE_MULTIPLE_CHOICE, AssignmentQuestion::TYPE_TRUE_FALSE], true)) {
                if ($rawOptions->count() < 2) {
                    throw ValidationException::withMessages(['options' => 'Add at least two options for objective assignment questions.']);
                }

                $correctIndexes = $questionType === AssignmentQuestion::TYPE_MULTIPLE_CHOICE
                    ? collect($validated['correct_option_indexes'] ?? [])->map(fn ($index): int => (int) $index)->unique()->values()
                    : collect([(int) ($validated['correct_option_index'] ?? -1)]);
                $availableIndexes = $rawOptions->pluck('index');
                $validCorrectIndexes = $correctIndexes->intersect($availableIndexes)->values();

                if ($validCorrectIndexes->isEmpty()) {
                    throw ValidationException::withMessages(['correct_option_index' => 'Choose the correct option for this assignment question.']);
                }

                if ($questionType !== AssignmentQuestion::TYPE_MULTIPLE_CHOICE && $validCorrectIndexes->count() !== 1) {
                    throw ValidationException::withMessages(['correct_option_index' => 'Choose exactly one correct option.']);
                }

                foreach ($rawOptions as $position => $option) {
                    AssignmentOption::create([
                        'assignment_question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => $validCorrectIndexes->contains($option['index']),
                        'sort_order' => $position + 1,
                    ]);
                }
            }
        }

        return back()->with('status', 'Assignment added.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.assignments.store');

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

    Route::get('/instructor/live-classes', function () use ($instructorCoursesQuery) {
        $user = Auth::user();

        $liveClasses = LiveClass::query()
            ->with(['course', 'module.course', 'lesson.module.course', 'attendances.user'])
            ->where('instructor_id', $user->id)
            ->orderBy('starts_at')
            ->get();

        return view('instructor.live-classes', [
            'liveClasses' => $liveClasses,
            'courses' => $instructorCoursesQuery($user)->orderBy('title')->get(),
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.index');

    Route::get('/instructor/live-classes/create', function (Request $request) use ($instructorCoursesQuery) {
        $user = Auth::user();
        $courses = $instructorCoursesQuery($user)->orderBy('title')->get();

        abort_if($courses->isEmpty(), 403);

        return view('instructor.live-class-form', [
            'liveClass' => new LiveClass([
                'course_id' => $courses->contains('id', $request->integer('course_id')) ? $request->integer('course_id') : $courses->first()->id,
                'platform' => LiveClass::PLATFORM_ZOOM,
                'status' => LiveClass::STATUS_SCHEDULED,
                'starts_at' => now()->addDay()->startOfHour(),
                'ends_at' => now()->addDay()->startOfHour()->addHour(),
            ]),
            'courses' => $courses,
            'mode' => 'create',
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.create');

    Route::post('/instructor/live-classes', function (Request $request) use ($validateInstructorLiveClass) {
        $user = Auth::user();
        $validated = $validateInstructorLiveClass($request, $user);

        $liveClass = LiveClass::create([
            ...$validated,
            'module_id' => null,
            'lesson_id' => null,
            'instructor_id' => $user->id,
        ]);

        return redirect()
            ->route('instructor.live-classes.edit', $liveClass)
            ->with('status', 'Live class saved.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.store');

    Route::get('/instructor/live-classes/{liveClass}/edit', function (LiveClass $liveClass) use ($abortUnlessInstructorLiveClass, $instructorCoursesQuery) {
        $user = Auth::user();
        $abortUnlessInstructorLiveClass($user, $liveClass);

        return view('instructor.live-class-form', [
            'liveClass' => $liveClass,
            'courses' => $instructorCoursesQuery($user)->orderBy('title')->get(),
            'mode' => 'edit',
        ]);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.edit');

    Route::put('/instructor/live-classes/{liveClass}', function (Request $request, LiveClass $liveClass) use ($abortUnlessInstructorLiveClass, $validateInstructorLiveClass) {
        $user = Auth::user();
        $abortUnlessInstructorLiveClass($user, $liveClass);
        $validated = $validateInstructorLiveClass($request, $user);

        $liveClass->update([
            ...$validated,
            'module_id' => null,
            'lesson_id' => null,
            'instructor_id' => $user->id,
        ]);

        return redirect()
            ->route('instructor.live-classes.edit', $liveClass)
            ->with('status', 'Live class updated.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.update');

    Route::post('/instructor/live-classes/{liveClass}/cancel', function (LiveClass $liveClass) use ($abortUnlessInstructorLiveClass) {
        $user = Auth::user();
        $abortUnlessInstructorLiveClass($user, $liveClass);

        $liveClass->update(['status' => LiveClass::STATUS_CANCELLED]);

        return redirect()
            ->route('instructor.live-classes.index')
            ->with('status', 'Live class cancelled.');
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.cancel');

    Route::get('/instructor/live-classes/{liveClass}/join', function (LiveClass $liveClass) use ($abortUnlessInstructorLiveClass) {
        $user = Auth::user();
        $abortUnlessInstructorLiveClass($user, $liveClass);

        if ($liveClass->isUpcoming()) {
            return back()->withErrors(['live_class' => 'Class has not started yet.']);
        }

        if ($liveClass->isEnded()) {
            return back()->withErrors(['live_class' => 'Class has ended.']);
        }

        if ($liveClass->status === LiveClass::STATUS_CANCELLED) {
            return back()->withErrors(['live_class' => 'This class has been cancelled.']);
        }

        if (! filled($liveClass->meeting_url)) {
            return back()->withErrors(['live_class' => 'Meeting link is not available.']);
        }

        if (! $liveClass->canJoin()) {
            return back()->withErrors(['live_class' => 'Class is not available to join right now.']);
        }

        return redirect()->away($liveClass->meeting_url);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.join');

    Route::get('/instructor/live-classes/{liveClass}/recording', function (LiveClass $liveClass) use ($abortUnlessInstructorLiveClass) {
        $user = Auth::user();
        $abortUnlessInstructorLiveClass($user, $liveClass);

        if ($liveClass->status === LiveClass::STATUS_CANCELLED) {
            return back()->withErrors(['live_class' => 'This class has been cancelled.']);
        }

        if (! $liveClass->canWatchRecording()) {
            return back()->withErrors(['live_class' => 'Recording is not available yet.']);
        }

        return redirect()->away($liveClass->recording_url);
    })->middleware('role:'.User::ROLE_INSTRUCTOR)->name('instructor.live-classes.recording');

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



