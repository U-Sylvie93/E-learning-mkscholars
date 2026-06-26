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

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_INSTRUCTOR,
        self::ROLE_MENTOR,
        self::ROLE_ADMIN,
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
        'approval_status',
        'approved_at',
        'approved_by',
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
            self::ROLE_ADMIN => '/admin',
            default => '/student/dashboard',
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->role === self::ROLE_ADMIN && $this->isApproved();
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



