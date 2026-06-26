<?php

namespace App\Models;

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
}
