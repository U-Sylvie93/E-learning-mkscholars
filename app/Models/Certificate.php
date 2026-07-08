<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Certificate extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'verification_code',
        'student_name',
        'course_title',
        'score',
        'signer_name',
        'signer_title',
        'signature_image_path',
        'status',
        'issued_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate): void {
            $certificate->certificate_number ??= self::generateCertificateNumber();
            $certificate->verification_code ??= self::generateVerificationCode();
            $certificate->issued_at ??= now();
            $certificate->status ??= self::STATUS_ISSUED;
            $certificate->score ??= self::finalTestScoreFor($certificate->user_id, $certificate->course_id);
        });
    }

    public static function generateCertificateNumber(): string
    {
        do {
            $number = 'MKS-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('certificate_number', $number)->exists());

        return $number;
    }

    public static function generateVerificationCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (self::where('verification_code', $code)->exists());

        return $code;
    }

    public static function finalTestScoreFor(User|int|null $user, Course|int|null $course): ?int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $courseId = $course instanceof Course ? $course->id : $course;

        if (! $userId || ! $courseId) {
            return null;
        }

        $finalTest = Quiz::query()
            ->where('course_id', $courseId)
            ->where('quiz_type', Quiz::TYPE_FINAL_TEST)
            ->where('status', Quiz::STATUS_PUBLISHED)
            ->first();

        if (! $finalTest) {
            return null;
        }

        return QuizAttempt::query()
            ->where('quiz_id', $finalTest->id)
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_PASSED,
                QuizAttempt::STATUS_FAILED,
            ])
            ->get()
            ->map(fn (QuizAttempt $attempt): ?int => self::percentageFromAttempt($attempt))
            ->filter(fn (?int $percentage): bool => $percentage !== null)
            ->max();
    }

    public function displayScore(): ?int
    {
        return $this->score ?? self::finalTestScoreFor($this->user_id, $this->course_id);
    }

    private static function percentageFromAttempt(QuizAttempt $attempt): ?int
    {
        if ($attempt->percentage !== null) {
            return max(0, min(100, (int) $attempt->percentage));
        }

        if ($attempt->total_points > 0) {
            return max(0, min(100, (int) round(($attempt->score / $attempt->total_points) * 100)));
        }

        return null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CertificateSkill::class);
    }

    public function signatureImageExists(): bool
    {
        try {
            return filled($this->signature_image_path)
                && Storage::disk('public')->exists($this->signature_image_path);
        } catch (Throwable) {
            return false;
        }
    }

    public function signatureImageUrl(): ?string
    {
        if (! $this->signatureImageExists()) {
            return null;
        }

        return Storage::disk('public')->url($this->signature_image_path);
    }

    public function signatureImageDataUri(): ?string
    {
        if (! $this->signatureImageExists()) {
            return null;
        }

        try {
            $contents = Storage::disk('public')->get($this->signature_image_path);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $path = Storage::disk('public')->path($this->signature_image_path);
        $mime = is_file($path) ? (@mime_content_type($path) ?: null) : null;

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            $mime = 'image/png';
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
