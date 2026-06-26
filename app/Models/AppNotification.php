<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasFactory;

    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER = 'danger';
    public const TYPE_REMINDER = 'reminder';

    public const CATEGORY_COURSE = 'course';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_ASSIGNMENT = 'assignment';
    public const CATEGORY_QUIZ = 'quiz';
    public const CATEGORY_LIVE_CLASS = 'live_class';
    public const CATEGORY_MENTORSHIP = 'mentorship';
    public const CATEGORY_OPPORTUNITY = 'opportunity';
    public const CATEGORY_APPLICATION = 'application';
    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_SYSTEM = 'system';

    public const TYPES = [
        self::TYPE_INFO,
        self::TYPE_SUCCESS,
        self::TYPE_WARNING,
        self::TYPE_DANGER,
        self::TYPE_REMINDER,
    ];

    public const CATEGORIES = [
        self::CATEGORY_COURSE,
        self::CATEGORY_PAYMENT,
        self::CATEGORY_ASSIGNMENT,
        self::CATEGORY_QUIZ,
        self::CATEGORY_LIVE_CLASS,
        self::CATEGORY_MENTORSHIP,
        self::CATEGORY_OPPORTUNITY,
        self::CATEGORY_APPLICATION,
        self::CATEGORY_CERTIFICATE,
        self::CATEGORY_SYSTEM,
    ];

    protected $fillable = [
        'user_id',
        'role',
        'title',
        'message',
        'type',
        'category',
        'action_url',
        'read_at',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
