<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentSubmission extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GRADED = 'graded';
    public const STATUS_RESUBMISSION_REQUIRED = 'resubmission_required';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'text_answer',
        'file_path',
        'external_link',
        'score',
        'feedback',
        'status',
        'submitted_at',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(AssignmentQuestionAnswer::class);
    }
}
