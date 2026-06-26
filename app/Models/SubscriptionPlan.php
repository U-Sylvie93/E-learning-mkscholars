<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';
    public const BILLING_CUSTOM = 'custom';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_amount',
        'currency',
        'billing_cycle',
        'duration_days',
        'status',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'features' => 'array',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'plan_courses')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function priceLabel(): string
    {
        return number_format((float) $this->price_amount, 0).' '.($this->currency ?: 'RWF');
    }

    public function durationDays(): int
    {
        if ($this->duration_days) {
            return (int) $this->duration_days;
        }

        return match ($this->billing_cycle) {
            self::BILLING_YEARLY => 365,
            self::BILLING_CUSTOM => 30,
            default => 30,
        };
    }
}
