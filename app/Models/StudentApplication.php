<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentApplication extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
    ];

    protected $fillable = [
        'opportunity_id',
        'user_id',
        'status',
        'notes',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (StudentApplication $application): void {
            $application->statusHistories()->create([
                'new_status' => $application->status,
                'changed_by' => $application->user_id,
                'note' => 'Application created.',
            ]);
        });

        static::updated(function (StudentApplication $application): void {
            if (! $application->wasChanged('status')) {
                return;
            }

            $application->statusHistories()->create([
                'old_status' => $application->getOriginal('status'),
                'new_status' => $application->status,
                'changed_by' => auth()->id(),
                'note' => 'Status updated.',
            ]);
        });
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }
}
