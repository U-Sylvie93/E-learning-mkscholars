<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCompletionRule extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'course_id',
        'required_lesson_percentage',
        'require_all_lessons',
        'required_quiz_percentage',
        'require_all_published_quizzes_passed',
        'require_all_published_assignments_submitted',
        'require_final_quiz_passed',
        'final_quiz_id',
        'required_live_class_attendance_percentage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'require_all_lessons' => 'boolean',
            'require_all_published_quizzes_passed' => 'boolean',
            'require_all_published_assignments_submitted' => 'boolean',
            'require_final_quiz_passed' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function finalQuiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'final_quiz_id');
    }
}
