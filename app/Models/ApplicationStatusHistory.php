<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_application_id',
        'old_status',
        'new_status',
        'changed_by',
        'note',
    ];

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
