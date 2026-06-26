<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    use HasFactory;

    public const TYPE_SCHOLARSHIP = 'scholarship';
    public const TYPE_INTERNSHIP = 'internship';
    public const TYPE_JOB = 'job';
    public const TYPE_STUDY_ABROAD = 'study_abroad';
    public const TYPE_COMPETITION = 'competition';
    public const TYPE_EVENT = 'event';

    public const TYPES = [
        self::TYPE_SCHOLARSHIP,
        self::TYPE_INTERNSHIP,
        self::TYPE_JOB,
        self::TYPE_STUDY_ABROAD,
        self::TYPE_COMPETITION,
        self::TYPE_EVENT,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'organization',
        'country',
        'city',
        'description',
        'requirements',
        'benefits',
        'application_url',
        'deadline',
        'status',
        'is_featured',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_featured' => 'boolean',
        ];
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(OpportunityRequirement::class);
    }

    public function studentApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function toPublicCard(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => str_replace('_', ' ', $this->type),
            'deadline' => $this->deadline?->format('M j, Y') ?? 'Open',
            'summary' => str($this->description)->limit(140)->toString(),
            'organization' => $this->organization,
            'country' => $this->country,
            'city' => $this->city,
            'deadline_badge' => $this->deadlineBadge(),
            'deadline_tone' => $this->deadlineBadgeTone(),
        ];
    }

    public function deadlineBadge(): string
    {
        if ($this->status === self::STATUS_CLOSED || ($this->deadline && $this->deadline->isPast() && ! $this->deadline->isToday())) {
            return 'Closed';
        }

        if ($this->deadline?->isToday()) {
            return 'Deadline today';
        }

        if ($this->deadline && $this->deadline->between(now(), now()->addDays(7))) {
            return 'Closing soon';
        }

        return 'Open';
    }

    public function deadlineBadgeTone(): string
    {
        return match ($this->deadlineBadge()) {
            'Closed' => 'gray',
            'Deadline today' => 'gold',
            'Closing soon' => 'blue',
            default => 'green',
        };
    }
}
