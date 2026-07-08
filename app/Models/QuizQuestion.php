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

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const TYPES = [
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_TRUE_FALSE,
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
}

