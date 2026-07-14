<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentQuestion extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_TRUE_FALSE,
    ];

    protected $fillable = [
        'assignment_id',
        'question_text',
        'question_type',
        'points',
        'sort_order',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AssignmentQuestion $question): void {
            if (! in_array($question->question_type, self::TYPES, true)) {
                $question->question_type = self::TYPE_TEXTAREA;
            }
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssignmentQuestionAnswer::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(AssignmentOption::class)->orderBy('sort_order')->orderBy('id');
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
            self::TYPE_TEXT,
            self::TYPE_TEXTAREA,
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
}
