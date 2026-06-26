<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_percentage',
        'quiz_percentage',
        'assignment_percentage',
        'live_attendance_percentage',
        'is_eligible_for_certificate',
        'completed_at',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_eligible_for_certificate' => 'boolean',
            'completed_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
