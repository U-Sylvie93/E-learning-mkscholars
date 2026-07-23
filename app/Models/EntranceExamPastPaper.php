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
        'instructions',
        'exam_year',
        'exam_type',
        'paper_file_path',
        'paper_file_disk',
        'paper_file_mime',
        'price_amount',
        'currency',
        'page_count',
        'is_featured',
        'status',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_year' => 'integer',
            'price_amount' => 'decimal:2',
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
            $paper->currency ??= 'RWF';
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
        return $this->mainPaperDisk();
    }

    public function paperFilePath(): ?string
    {
        return $this->mainPaperPath();
    }

    public function mainPaperPath(): ?string
    {
        foreach (['paper_file_path', 'file_path', 'document_path', 'paper_path'] as $key) {
            $value = $this->getAttribute($key);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    public function mainPaperDisk(): string
    {
        foreach (['paper_file_disk', 'disk', 'paper_disk', 'file_disk'] as $key) {
            $value = $this->getAttribute($key);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return 'public';
    }

    public function paperFileExtension(): ?string
    {
        return $this->mainPaperExtension();
    }

    public function mainPaperExtension(): ?string
    {
        $path = $this->mainPaperPath();

        if (blank($path)) {
            return null;
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: null;
    }

    public function normalizedPaperMime(): ?string
    {
        return $this->mainPaperMime();
    }

    public function mainPaperMime(): ?string
    {
        $mime = null;

        foreach (['paper_file_mime', 'mime', 'mime_type', 'paper_mime', 'file_mime', 'resource_mime'] as $key) {
            $value = strtolower(trim((string) $this->getAttribute($key)));

            if ($value !== '') {
                $mime = $value;
                break;
            }
        }

        if (filled($mime) && $mime !== 'application/octet-stream') {
            return $mime;
        }

        return match ($this->mainPaperExtension()) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => $mime ?: null,
        };
    }

    public function hasPaperFile(): bool
    {
        return filled($this->paperFilePath());
    }

    public function hasPdfFile(): bool
    {
        return $this->isPdf();
    }

    public function isPdf(): bool
    {
        return $this->isPdfPaper();
    }

    public function isPdfPaper(): bool
    {
        return $this->mainPaperExtension() === 'pdf'
            || $this->mainPaperMime() === 'application/pdf';
    }

    public function hasImageFile(): bool
    {
        return $this->isImage();
    }

    public function isImage(): bool
    {
        return $this->isImagePaper();
    }

    public function isImagePaper(): bool
    {
        return in_array($this->mainPaperExtension(), ['png', 'jpg', 'jpeg', 'webp'], true)
            || str_starts_with((string) $this->mainPaperMime(), 'image/');
    }

    public function hasOfficeFile(): bool
    {
        if (! $this->hasPaperFile()) {
            return false;
        }

        $extension = $this->paperFileExtension();

        return in_array($extension, ['doc', 'docx', 'ppt', 'pptx'], true)
            || in_array($this->normalizedPaperMime(), [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ], true);
    }

    public function isOfficeDocument(): bool
    {
        return $this->isOfficePaper();
    }

    public function isOfficePaper(): bool
    {
        return in_array($this->mainPaperExtension(), ['doc', 'docx', 'ppt', 'pptx'], true);
    }

    public function canPreviewInline(): bool
    {
        return $this->isPdfPaper() || $this->isImagePaper();
    }

    public function viewerKind(): string
    {
        return match (true) {
            $this->isPdf() => 'pdf',
            $this->isImage() => 'image',
            $this->isOfficeDocument() => 'office',
            default => 'unsupported',
        };
    }

    public function viewerMime(): ?string
    {
        return match ($this->viewerKind()) {
            'pdf' => 'application/pdf',
            'image' => $this->normalizedPaperMime(),
            default => null,
        };
    }

    public function isFree(): bool
    {
        return (float) ($this->price_amount ?? 0) <= 0;
    }

    public function priceLabel(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return number_format((float) $this->price_amount, 0).' '.($this->currency ?: 'RWF');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    private static function uniqueSlug(?string $value, ?int $ignoreId = null): string
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
