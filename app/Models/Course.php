<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const ACCESS_FREE = 'free';
    public const ACCESS_PAID = 'paid';

    public const TIER_BASIC = 'basic';
    public const TIER_PREMIUM = 'premium';
    public const TIERS = [self::TIER_BASIC, self::TIER_PREMIUM];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'academy_id',
        'instructor_id',
        'title',
        'slug',
        'short_description',
        'full_description',
        'level',
        'duration',
        'price',
        'is_free',
        'price_amount',
        'price_basic',
        'price_premium',
        'currency',
        'access_type',
        'offers_certificate',
        'status',
        'featured_image_path',
        'learning_outcomes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'offers_certificate' => 'boolean',
            'price_amount' => 'decimal:2',
            'price_basic' => 'decimal:2',
            'price_premium' => 'decimal:2',
            'learning_outcomes' => 'array',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function ownedBy(?User $user): bool
    {
        return $user !== null && (int) $this->instructor_id === (int) $user->id;
    }
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function finalTest(): HasOne
    {
        return $this->hasOne(Quiz::class)->where('quiz_type', Quiz::TYPE_FINAL_TEST);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function liveClasses(): HasMany
    {
        return $this->hasMany(LiveClass::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_courses')->withTimestamps();
    }

    public function completionRule(): HasOne
    {
        return $this->hasOne(CourseCompletionRule::class);
    }

    public function courseCompletions(): HasMany
    {
        return $this->hasMany(CourseCompletion::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function isFree(): bool
    {
        if (filled($this->access_type)) {
            return $this->access_type === self::ACCESS_FREE;
        }

        return (bool) $this->is_free;
    }

    public function requiresPayment(): bool
    {
        return ! $this->isFree();
    }

    public function payableAmount(?string $tier = null): float
    {
        if ($tier === self::TIER_BASIC && $this->price_basic !== null) {
            return (float) $this->price_basic;
        }

        if ($tier === self::TIER_PREMIUM && $this->price_premium !== null) {
            return (float) $this->price_premium;
        }

        if ($this->price_basic !== null && $this->price_premium !== null) {
            return (float) $this->price_basic;
        }

        return (float) ($this->price_amount ?? $this->price_basic ?? $this->price ?? 0);
    }

    public function hasTierPricing(): bool
    {
        return ($this->price_basic !== null && (float) $this->price_basic > 0)
            || ($this->price_premium !== null && (float) $this->price_premium > 0);
    }

    public function priceLabel(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        if ($this->hasTierPricing()) {
            $currency = $this->currency ?: 'RWF';
            $parts = [];
            if ($this->price_basic > 0) {
                $parts[] = 'Basic '.number_format((float) $this->price_basic, 0).' '.$currency;
            }
            if ($this->price_premium > 0) {
                $parts[] = 'Premium '.number_format((float) $this->price_premium, 0).' '.$currency;
            }

            return implode(' / ', $parts) ?: 'Paid course';
        }

        $amount = $this->payableAmount();

        return $amount > 0
            ? number_format($amount, 0).' '.($this->currency ?: 'RWF')
            : 'Paid course';
    }

    public function offersCertificate(): bool
    {
        return (bool) $this->offers_certificate;
    }

    public function toPublicCard(): array
    {
        $lessonsCount = $this->relationLoaded('modules')
            ? $this->modules->sum(fn (Module $module): int => $module->relationLoaded('lessons') ? $module->lessons->count() : 0)
            : 0;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'academy' => $this->academy?->name ?? 'MK Scholars',
            'academy_slug' => $this->academy?->slug,
            'academy_icon' => $this->academy?->safeIcon() ?? Academy::ICON_BOOK_OPEN,
            'academy_icon_label' => $this->academy?->iconLabel() ?? 'Academy',
            'level' => $this->level,
            'duration' => $this->duration,
            'price' => $this->priceLabel(),
            'is_free' => $this->isFree(),
            'access_type' => $this->isFree() ? self::ACCESS_FREE : self::ACCESS_PAID,
            'offers_certificate' => $this->offersCertificate(),
            'price_amount' => $this->payableAmount(),
            'price_basic' => $this->price_basic !== null ? (float) $this->price_basic : null,
            'price_premium' => $this->price_premium !== null ? (float) $this->price_premium : null,
            'has_tier_pricing' => $this->hasTierPricing(),
            'currency' => $this->currency ?: 'RWF',
            'summary' => $this->short_description,
            'outcomes' => $this->learning_outcomes ?: [],
            'image' => $this->coverImageUrl(),
            'lessons_count' => $lessonsCount,
        ];
    }

    public function coverImageUrl(): ?string
    {
        if (filled($this->featured_image_path)) {
            $path = (string) $this->featured_image_path;

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
                return null;
            }

            return asset('storage/'.$path);
        }

        return self::fallbackImageForAcademy($this->academy?->name);
    }

    public static function fallbackImageForAcademy(?string $academyName): ?string
    {
        return null;
    }
}




