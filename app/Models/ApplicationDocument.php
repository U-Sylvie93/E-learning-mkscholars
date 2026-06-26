<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPROVED = 'approved';

    public const STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
        self::STATUS_APPROVED,
    ];

    protected $fillable = [
        'student_application_id',
        'student_document_id',
        'document_name',
        'file_path',
        'external_link',
        'status',
        'admin_feedback',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }

    public function studentDocument(): BelongsTo
    {
        return $this->belongsTo(StudentDocument::class);
    }
}
