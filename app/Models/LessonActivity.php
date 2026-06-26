<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'activity_type',
        'type',
        'title',
        'instructions',
        'resource_url',
        'metadata',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
