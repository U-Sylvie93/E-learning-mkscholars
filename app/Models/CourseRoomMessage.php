<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRoomMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_room_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'deleted_at',
        'deleted_by_id',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function isDeleted(): bool
    {
        return filled($this->deleted_at);
    }

    public function hasAttachment(): bool
    {
        return ! $this->isDeleted() && filled($this->attachment_path);
    }

    public function isImageAttachment(): bool
    {
        return $this->hasAttachment() && str_starts_with((string) $this->attachment_mime, 'image/');
    }

    public function attachmentUrl(): ?string
    {
        return $this->hasAttachment()
            ? asset('storage/'.$this->attachment_path)
            : null;
    }

    public function humanAttachmentSize(): string
    {
        $bytes = (int) ($this->attachment_size ?? 0);
        if ($bytes <= 0) {
            return '';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }
        return $bytes.' B';
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(CourseRoom::class, 'course_room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
