<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    public const TYPE_LESSON_QUIZ = 'lesson_quiz';
    public const TYPE_FINAL_TEST = 'final_test';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPES = [
        self::TYPE_LESSON_QUIZ,
        self::TYPE_FINAL_TEST,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'lesson_id',
        'course_id',
        'quiz_type',
        'title',
        'description',
        'passing_score',
        'max_attempts',
        'time_limit_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
            'time_limit_minutes' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isFinalTest(): bool
    {
        return $this->quiz_type === self::TYPE_FINAL_TEST;
    }

    public function courseContext(): ?Course
    {
        return $this->course ?? $this->lesson?->module?->course;
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}

