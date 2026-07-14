<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EntranceExamProgram extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'entrance_exam_institution_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (EntranceExamProgram $program): void {
            if (blank($program->slug)) {
                $program->slug = static::uniqueSlug($program->name, $program->id);
            } else {
                $program->slug = static::uniqueSlug($program->slug, $program->id);
            }

            $program->status ??= self::STATUS_ACTIVE;
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(EntranceExamInstitution::class, 'entrance_exam_institution_id');
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

    private static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'program';
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
