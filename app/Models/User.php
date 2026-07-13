<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_INSTRUCTOR = 'instructor';
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_VIEWER = 'viewer';
    public const ROLE_CONTENT_EDITOR = 'content_editor';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_INSTRUCTOR,
        self::ROLE_MENTOR,
        self::ROLE_ADMIN,
        self::ROLE_VIEWER,
        self::ROLE_CONTENT_EDITOR,
    ];

    public const VIEWER_PERMISSION_USERS = 'users_view';
    public const VIEWER_PERMISSION_COURSES = 'courses_view';
    public const VIEWER_PERMISSION_PAYMENTS = 'payments_view';
    public const VIEWER_PERMISSION_SUBSCRIPTIONS = 'subscriptions_view';
    public const VIEWER_PERMISSION_CERTIFICATES = 'certificates_view';
    public const VIEWER_PERMISSION_REPORTS = 'reports_view';
    public const VIEWER_PERMISSION_QUIZZES = 'quizzes_view';
    public const VIEWER_PERMISSION_ASSIGNMENTS = 'assignments_view';

    public const VIEWER_PERMISSIONS = [
        self::VIEWER_PERMISSION_USERS,
        self::VIEWER_PERMISSION_COURSES,
        self::VIEWER_PERMISSION_PAYMENTS,
        self::VIEWER_PERMISSION_SUBSCRIPTIONS,
        self::VIEWER_PERMISSION_CERTIFICATES,
        self::VIEWER_PERMISSION_REPORTS,
        self::VIEWER_PERMISSION_QUIZZES,
        self::VIEWER_PERMISSION_ASSIGNMENTS,
    ];

    public const CONTENT_PERMISSION_COURSES_CREATE = 'courses_create';
    public const CONTENT_PERMISSION_COURSES_EDIT = 'courses_edit';
    public const CONTENT_PERMISSION_MODULES_MANAGE = 'modules_manage';
    public const CONTENT_PERMISSION_LESSONS_MANAGE = 'lessons_manage';
    public const CONTENT_PERMISSION_QUIZZES_MANAGE = 'quizzes_manage';
    public const CONTENT_PERMISSION_FINAL_TESTS_MANAGE = 'final_tests_manage';
    public const CONTENT_PERMISSION_ASSIGNMENTS_MANAGE = 'assignments_manage';

    public const CONTENT_PERMISSIONS = [
        self::CONTENT_PERMISSION_COURSES_CREATE,
        self::CONTENT_PERMISSION_COURSES_EDIT,
        self::CONTENT_PERMISSION_MODULES_MANAGE,
        self::CONTENT_PERMISSION_LESSONS_MANAGE,
        self::CONTENT_PERMISSION_QUIZZES_MANAGE,
        self::CONTENT_PERMISSION_FINAL_TESTS_MANAGE,
        self::CONTENT_PERMISSION_ASSIGNMENTS_MANAGE,
    ];

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_SUSPENDED = 'suspended';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
        self::APPROVAL_SUSPENDED,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'signature_path',
        'approval_status',
        'approved_at',
        'approved_by',
        'viewer_permissions',
        'content_permissions',
        'content_course_ids',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'viewer_permissions' => 'array',
            'content_permissions' => 'array',
            'content_course_ids' => 'array',
        ];
    }

    public function isApproved(): bool
    {
        return ($this->approval_status ?? self::APPROVAL_APPROVED) === self::APPROVAL_APPROVED;
    }

    public function requiresApproval(): bool
    {
        return in_array($this->role, [self::ROLE_INSTRUCTOR, self::ROLE_MENTOR], true);
    }

    public function canAccessProtectedArea(): bool
    {
        return $this->isApproved();
    }

    public function approvalMessage(): string
    {
        return match ($this->approval_status) {
            self::APPROVAL_PENDING => 'Your account is pending admin approval.',
            self::APPROVAL_REJECTED => 'Your account was not approved. Please contact MK Scholars support if you believe this is a mistake.',
            self::APPROVAL_SUSPENDED => 'Your account is suspended. Please contact MK Scholars support.',
            default => 'Your account is not approved for this area.',
        };
    }

    public function markApproved(?int $approvedBy = null): void
    {
        $this->forceFill([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ])->save();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }
    public function dashboardPath(): string
    {
        return match ($this->role) {
            self::ROLE_INSTRUCTOR => '/instructor/dashboard',
            self::ROLE_MENTOR => '/mentor/dashboard',
            self::ROLE_ADMIN,
            self::ROLE_VIEWER,
            self::ROLE_CONTENT_EDITOR => '/admin',
            default => '/student/dashboard',
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && in_array($this->role, [self::ROLE_ADMIN, self::ROLE_VIEWER, self::ROLE_CONTENT_EDITOR], true)
            && $this->isApproved();
    }

    public function isReadOnlyAdminViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function isContentEditor(): bool
    {
        return $this->role === self::ROLE_CONTENT_EDITOR;
    }

    public function hasViewerPermission(string $permission): bool
    {
        return in_array($permission, $this->viewer_permissions ?? [], true);
    }

    public function hasContentPermission(string $permission): bool
    {
        return in_array($permission, $this->content_permissions ?? [], true);
    }

    public function assignedContentCourseIds(): array
    {
        return collect($this->content_course_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function canManageContentCourse(?int $courseId): bool
    {
        return $this->isContentEditor()
            && $courseId !== null
            && in_array((int) $courseId, $this->assignedContentCourseIds(), true);
    }

    public function assignContentCourse(int $courseId): void
    {
        $courseIds = collect($this->assignedContentCourseIds())
            ->push($courseId)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->forceFill(['content_course_ids' => $courseIds])->save();
    }

    public static function viewerPermissionOptions(): array
    {
        return [
            self::VIEWER_PERMISSION_USERS => 'Users',
            self::VIEWER_PERMISSION_COURSES => 'Courses and course content',
            self::VIEWER_PERMISSION_PAYMENTS => 'Payments',
            self::VIEWER_PERMISSION_SUBSCRIPTIONS => 'Subscriptions',
            self::VIEWER_PERMISSION_CERTIFICATES => 'Certificates',
            self::VIEWER_PERMISSION_REPORTS => 'Reports',
            self::VIEWER_PERMISSION_QUIZZES => 'Quizzes and attempts',
            self::VIEWER_PERMISSION_ASSIGNMENTS => 'Assignments and submissions',
        ];
    }

    public static function contentPermissionOptions(): array
    {
        return [
            self::CONTENT_PERMISSION_COURSES_CREATE => 'Create courses',
            self::CONTENT_PERMISSION_COURSES_EDIT => 'Edit assigned courses',
            self::CONTENT_PERMISSION_MODULES_MANAGE => 'Manage modules on assigned courses',
            self::CONTENT_PERMISSION_LESSONS_MANAGE => 'Manage lessons and materials on assigned courses',
            self::CONTENT_PERMISSION_QUIZZES_MANAGE => 'Manage quizzes, questions, and options on assigned courses',
            self::CONTENT_PERMISSION_FINAL_TESTS_MANAGE => 'Manage final tests on assigned courses',
            self::CONTENT_PERMISSION_ASSIGNMENTS_MANAGE => 'Manage assignments on assigned courses',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function liveClassAttendances(): HasMany
    {
        return $this->hasMany(LiveClassAttendance::class);
    }

    public function instructedLiveClasses(): HasMany
    {
        return $this->hasMany(LiveClass::class, 'instructor_id');
    }

    public function instructedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }
    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'mentor_id');
    }

    public function studentMentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'student_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function createdOpportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'created_by');
    }

    public function studentApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function courseCompletions(): HasMany
    {
        return $this->hasMany(CourseCompletion::class);
    }

    public function courseReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function applicationStatusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'changed_by');
    }
}


