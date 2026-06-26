<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_submission_id',
        'assignment_question_id',
        'answer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssignmentQuestion::class, 'assignment_question_id');
    }
}
