<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentDocument extends Model
{
    use HasFactory;

    public const TYPE_PASSPORT = 'passport';
    public const TYPE_CV = 'cv';
    public const TYPE_MOTIVATION_LETTER = 'motivation_letter';
    public const TYPE_ACADEMIC_TRANSCRIPT = 'academic_transcript';
    public const TYPE_ENGLISH_TEST_RESULT = 'english_test_result';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_PASSPORT,
        self::TYPE_CV,
        self::TYPE_MOTIVATION_LETTER,
        self::TYPE_ACADEMIC_TRANSCRIPT,
        self::TYPE_ENGLISH_TEST_RESULT,
        self::TYPE_OTHER,
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'title',
        'file_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
