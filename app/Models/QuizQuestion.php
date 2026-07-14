<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SHORT_ANSWER = 'short_answer';
    public const TYPE_LONG_ANSWER = 'long_answer';
    public const TYPE_TEXT = 'text';
    public const TYPE_ESSAY = 'essay';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const TYPES = [
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_TRUE_FALSE,
        self::TYPE_SHORT_ANSWER,
        self::TYPE_LONG_ANSWER,
        self::TYPE_TEXT,
        self::TYPE_ESSAY,
    ];

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'points',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuizQuestion $question): void {
            if (! in_array($question->question_type, self::TYPES, true)) {
                $question->question_type = self::TYPE_MULTIPLE_CHOICE;
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function requiresOptions(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_SINGLE_CHOICE,
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_TRUE_FALSE,
        ], true);
    }

    public function acceptsSingleOption(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_SINGLE_CHOICE,
            self::TYPE_TRUE_FALSE,
        ], true);
    }

    public function acceptsMultipleOptions(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function acceptsTextAnswer(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_SHORT_ANSWER,
            self::TYPE_LONG_ANSWER,
            self::TYPE_TEXT,
            self::TYPE_ESSAY,
        ], true);
    }

    public function acceptsLongTextAnswer(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_LONG_ANSWER,
            self::TYPE_ESSAY,
        ], true);
    }

    public function acceptsFileAnswer(): bool
    {
        return false;
    }

    public function isTrueFalse(): bool
    {
        return $this->question_type === self::TYPE_TRUE_FALSE;
    }

    public function isAutoGradable(): bool
    {
        return $this->requiresOptions();
    }
}
