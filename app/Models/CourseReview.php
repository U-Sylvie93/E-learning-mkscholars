<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CourseReview extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PUBLISHED,
        self::STATUS_HIDDEN,
    ];

    protected $fillable = [
        'user_id',
        'course_id',
        'rating',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CourseReview $review): void {
            $review->status = $review->status ?: self::STATUS_PENDING;

            if ($review->rating < 1 || $review->rating > 5) {
                throw ValidationException::withMessages([
                    'rating' => 'Review rating must be between 1 and 5.',
                ]);
            }

            if (! in_array($review->status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Review status is invalid.',
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
