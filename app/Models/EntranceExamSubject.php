<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EntranceExamSubject extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (EntranceExamSubject $subject): void {
            if (blank($subject->slug)) {
                $subject->slug = static::uniqueSlug($subject->name, $subject->id);
            } else {
                $subject->slug = static::uniqueSlug($subject->slug, $subject->id);
            }

            $subject->status ??= self::STATUS_ACTIVE;
        });
    }

    public function pastPapers(): HasMany
    {
        return $this->hasMany(EntranceExamPastPaper::class);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    private static function uniqueSlug(?string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'subject';
        $slug = $base;
        $count = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }
}
