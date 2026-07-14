<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntranceExamPastPaper extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'entrance_exam_institution_id',
        'entrance_exam_program_id',
        'entrance_exam_subject_id',
        'title',
        'slug',
        'description',
        'exam_year',
        'exam_type',
        'paper_file_path',
        'paper_file_disk',
        'paper_file_mime',
        'page_count',
        'is_featured',
        'status',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_year' => 'integer',
            'page_count' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EntranceExamPastPaper $paper): void {
            if (blank($paper->slug)) {
                $paper->slug = static::uniqueSlug($paper->title, $paper->id);
            } else {
                $paper->slug = static::uniqueSlug($paper->slug, $paper->id);
            }

            $paper->paper_file_disk ??= 'public';
            $paper->status ??= self::STATUS_DRAFT;

            if (blank($paper->paper_file_mime) && filled($paper->paper_file_path)) {
                $disk = Storage::disk($paper->paperFileDisk());

                if ($disk->exists($paper->paper_file_path)) {
                    $paper->paper_file_mime = $disk->mimeType($paper->paper_file_path);
                }
            }
        });

        static::creating(function (EntranceExamPastPaper $paper): void {
            $paper->uploaded_by ??= auth()->id();
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(EntranceExamInstitution::class, 'entrance_exam_institution_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(EntranceExamProgram::class, 'entrance_exam_program_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(EntranceExamSubject::class, 'entrance_exam_subject_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function paperFileDisk(): string
    {
        return $this->paper_file_disk ?: 'public';
    }

    public function hasPdfFile(): bool
    {
        if (blank($this->paper_file_path)) {
            return false;
        }

        if (filled($this->paper_file_mime)) {
            return $this->paper_file_mime === 'application/pdf';
        }

        return str($this->paper_file_path)->lower()->endsWith('.pdf');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    private static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'past-paper';
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
