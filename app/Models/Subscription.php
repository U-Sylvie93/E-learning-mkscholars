<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'payment_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->status === self::STATUS_ACTIVE && $this->ends_at && $this->ends_at->isPast());
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isActive()
            && $this->ends_at
            && $this->ends_at->between(now(), now()->addDays($days));
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    public function statusTone(): string
    {
        return match ($this->statusLabel()) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PENDING => 'gold',
            self::STATUS_REJECTED => 'gold',
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }
}
