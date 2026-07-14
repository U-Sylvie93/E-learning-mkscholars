<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveClass extends Model
{
    use HasFactory;

    public const PLATFORM_ZOOM = 'zoom';
    public const PLATFORM_GOOGLE_MEET = 'google_meet';
    public const PLATFORM_TEAMS = 'teams';
    public const PLATFORM_OTHER = 'other';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE = 'live';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PLATFORMS = [
        self::PLATFORM_ZOOM,
        self::PLATFORM_GOOGLE_MEET,
        self::PLATFORM_TEAMS,
        self::PLATFORM_OTHER,
    ];

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'course_id',
        'module_id',
        'lesson_id',
        'instructor_id',
        'title',
        'description',
        'meeting_url',
        'platform',
        'starts_at',
        'ends_at',
        'status',
        'recording_url',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LiveClassAttendance::class);
    }

    public function associatedCourse(): ?Course
    {
        return $this->course ?? $this->module?->course ?? $this->lesson?->module?->course;
    }

    public function isUpcoming(?CarbonInterface $now = null): bool
    {
        if ($this->status === self::STATUS_CANCELLED || ! $this->starts_at) {
            return false;
        }

        return ($now ?? now())->lt($this->starts_at);
    }

    public function isLiveNow(?CarbonInterface $now = null): bool
    {
        if ($this->status === self::STATUS_CANCELLED || ! $this->starts_at || ! $this->ends_at) {
            return false;
        }

        $now ??= now();

        return $now->betweenIncluded($this->starts_at, $this->ends_at);
    }

    public function isEnded(?CarbonInterface $now = null): bool
    {
        if ($this->status === self::STATUS_CANCELLED || ! $this->ends_at) {
            return false;
        }

        return ($now ?? now())->gt($this->ends_at);
    }

    public function canJoin(?CarbonInterface $now = null): bool
    {
        return $this->status !== self::STATUS_CANCELLED && filled($this->meeting_url);
    }

    public function canWatchRecording(?CarbonInterface $now = null): bool
    {
        return $this->status !== self::STATUS_CANCELLED && filled($this->recording_url);
    }

    public function displayStatus(?CarbonInterface $now = null): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'Cancelled';
        }

        if ($this->isLiveNow($now)) {
            return 'Live Now';
        }

        if ($this->isEnded($now)) {
            return $this->canWatchRecording($now) ? 'Recording Available' : 'Ended';
        }

        if ($this->isUpcoming($now)) {
            return 'Upcoming';
        }

        return str($this->status ?: self::STATUS_SCHEDULED)->replace('_', ' ')->title()->toString();
    }

    public function displayStatusTone(?CarbonInterface $now = null): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'danger';
        }

        if ($this->isLiveNow($now) || ($this->isEnded($now) && $this->canWatchRecording($now))) {
            return 'green';
        }

        if ($this->isEnded($now)) {
            return 'gray';
        }

        return 'blue';
    }
}
