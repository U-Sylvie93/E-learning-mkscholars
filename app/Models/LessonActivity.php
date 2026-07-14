<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'resource_path',
        'resource_disk',
        'resource_mime',
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

    protected static function booted(): void
    {
        static::saving(function (LessonActivity $activity): void {
            if (filled($activity->resource_path)) {
                $activity->resource_disk ??= 'public';

                if (blank($activity->resource_mime) && Storage::disk($activity->resourceDisk())->exists($activity->resource_path)) {
                    $activity->resource_mime = Storage::disk($activity->resourceDisk())->mimeType($activity->resource_path);
                }
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function hasUploadedResource(): bool
    {
        return filled($this->resource_path);
    }

    public function isPdfResource(): bool
    {
        if (filled($this->resource_mime)) {
            return $this->resource_mime === 'application/pdf';
        }

        return str($this->resource_path ?? $this->resource_url ?? '')->lower()->endsWith('.pdf');
    }

    public function isImageResource(): bool
    {
        if (filled($this->resource_mime)) {
            return str_starts_with((string) $this->resource_mime, 'image/');
        }

        return Str::is(['*.png', '*.jpg', '*.jpeg', '*.webp'], strtolower($this->resource_path ?? $this->resource_url ?? ''));
    }

    public function resourceDisk(): string
    {
        return $this->resource_disk ?: 'public';
    }
}
