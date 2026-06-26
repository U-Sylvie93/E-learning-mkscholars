<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'slug',
        'summary',
        'lesson_type',
        'video_url',
        'content',
        'duration_minutes',
        'estimated_minutes',
        'sort_order',
        'is_free_preview',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_free_preview' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LessonActivity::class);
    }

    public function lessonActivities(): HasMany
    {
        return $this->hasMany(LessonActivity::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function liveClasses(): HasMany
    {
        return $this->hasMany(LiveClass::class);
    }
}
