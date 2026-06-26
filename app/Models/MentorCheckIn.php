<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorCheckIn extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MISSED = 'missed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'mentor_assignment_id',
        'scheduled_at',
        'completed_at',
        'topic',
        'student_notes',
        'mentor_feedback',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function mentorAssignment(): BelongsTo
    {
        return $this->belongsTo(MentorAssignment::class);
    }
}
