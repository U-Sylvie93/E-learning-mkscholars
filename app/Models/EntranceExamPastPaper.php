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
        return $this->paper_file_disk ?: 'public';
    }

    public function hasPaperFile(): bool
    {
        return filled($this->paper_file_path);
    }

    public function hasPdfFile(): bool
    {
        return $this->isPdfFile($this->paper_file_path, $this->paper_file_mime);
    }

    public function isPdf(): bool
    {
        return $this->hasPdfFile();
    }

    public function hasImageFile(): bool
    {
        return $this->isImageFile($this->paper_file_path, $this->paper_file_mime);
    }

    public function isImage(): bool
    {
        return $this->hasImageFile();
    }

    public function hasOfficeFile(): bool
    {
        if (blank($this->paper_file_path)) {
            return false;
        }

        $extension = str($this->paper_file_path)->afterLast('.')->lower()->toString();

        return in_array($extension, ['doc', 'docx', 'ppt', 'pptx'], true)
            || in_array($this->paper_file_mime, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ], true);
    }

    public function isOfficeDocument(): bool
    {
        return $this->hasOfficeFile();
    }

    public function canPreviewInline(): bool
    {
        return $this->hasPdfFile() || $this->hasImageFile();
    }

    public function viewerKind(): string
    {
        return match (true) {
            $this->hasPdfFile() => 'pdf',
            $this->hasImageFile() => 'image',
            $this->hasOfficeFile() => 'office',
            default => 'unsupported',
        };
    }

    public function viewerMime(): ?string
    {
        return match ($this->viewerKind()) {
            'pdf' => 'application/pdf',
            'image' => $this->paper_file_mime ?: $this->imageMimeFromExtension($this->paper_file_path),
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

    private function isPdfFile(?string $path, ?string $mime): bool
    {
        if (blank($path)) {
            return false;
        }

        return $mime === 'application/pdf' || str($path)->lower()->endsWith('.pdf');
    }

    private function isImageFile(?string $path, ?string $mime): bool
    {
        if (blank($path)) {
            return false;
        }

        if (in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return true;
        }

        return str($path)->lower()->endsWith(['.png', '.jpg', '.jpeg', '.webp']);
    }

    private function imageMimeFromExtension(?string $path): ?string
    {
        return match (str($path)->afterLast('.')->lower()->toString()) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => null,
        };
    }
}
