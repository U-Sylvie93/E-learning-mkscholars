<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    public const PROVIDER_MANUAL = 'manual';
    public const PROVIDER_MTN_MOMO = 'mtn_momo';
    public const PROVIDER_AIRTEL_MONEY = 'airtel_money';
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_PAYPAL = 'paypal';

    public const PURPOSE_COURSE = 'course';
    public const PURPOSE_SUBSCRIPTION = 'subscription';
    public const PURPOSE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'course_id',
        'payment_method_id',
        'amount',
        'currency',
        'purpose',
        'provider',
        'provider_reference',
        'provider_status',
        'provider_payload',
        'provider_callback_received_at',
        'status',
        'reference',
        'proof_path',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_payload' => 'array',
            'provider_callback_received_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->provider ??= self::PROVIDER_MANUAL;
        });

        static::updated(function (Payment $payment): void {
            if (! $payment->wasChanged('status')) {
                return;
            }

            if ($payment->status === self::STATUS_APPROVED && $payment->purpose === self::PURPOSE_COURSE && $payment->course_id) {
                Enrollment::updateOrCreate(
                    [
                        'user_id' => $payment->user_id,
                        'course_id' => $payment->course_id,
                    ],
                    [
                        'status' => Enrollment::STATUS_ACTIVE,
                        'enrolled_at' => now(),
                        'completed_at' => null,
                    ],
                );
            }

            if ($payment->status === self::STATUS_APPROVED && $payment->purpose === self::PURPOSE_SUBSCRIPTION) {
                $subscription = $payment->subscription()->with('subscriptionPlan')->first();

                if ($subscription && $subscription->subscriptionPlan) {
                    $alreadyProcessedRenewal = $subscription->status === Subscription::STATUS_ACTIVE
                        && $payment->getOriginal('reviewed_at') !== null;

                    if ($alreadyProcessedRenewal) {
                        return;
                    }

                    $startsAt = $subscription->starts_at && $subscription->starts_at->isPast()
                        ? $subscription->starts_at
                        : now();
                    $extensionBase = $subscription->ends_at && $subscription->ends_at->isFuture()
                        ? $subscription->ends_at
                        : now();

                    $subscription->update([
                        'status' => Subscription::STATUS_ACTIVE,
                        'starts_at' => $startsAt,
                        'ends_at' => $extensionBase->copy()->addDays($subscription->subscriptionPlan->durationDays()),
                        'cancelled_at' => null,
                    ]);
                }
            }

            if ($payment->status === self::STATUS_REJECTED && $payment->purpose === self::PURPOSE_SUBSCRIPTION) {
                $payment->subscription()->where('status', Subscription::STATUS_PENDING)->update([
                    'status' => Subscription::STATUS_REJECTED,
                ]);
            }
        });
    }

    public function isManualProvider(): bool
    {
        return ($this->provider ?: self::PROVIDER_MANUAL) === self::PROVIDER_MANUAL;
    }

    public function isExternalProvider(): bool
    {
        return ! $this->isManualProvider();
    }

    public function hasProviderReference(): bool
    {
        return filled($this->provider_reference);
    }

    public function providerLabel(): string
    {
        return match ($this->provider ?: self::PROVIDER_MANUAL) {
            self::PROVIDER_MANUAL => 'Manual',
            self::PROVIDER_MTN_MOMO => 'MTN MoMo',
            self::PROVIDER_AIRTEL_MONEY => 'Airtel Money',
            self::PROVIDER_STRIPE => 'Stripe',
            self::PROVIDER_PAYPAL => 'PayPal',
            default => str($this->provider)->replace('_', ' ')->headline()->toString(),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
