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

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
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
}

