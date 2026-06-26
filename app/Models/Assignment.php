<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_FILE = 'file';
    public const TYPE_LINK = 'link';
    public const TYPE_MIXED = 'mixed';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'lesson_id',
        'title',
        'instructions',
        'instruction_file_path',
        'submission_type',
        'max_score',
        'due_days_after_enrollment',
        'allow_late_submission',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allow_late_submission' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class)->orderBy('sort_order');
    }
}
