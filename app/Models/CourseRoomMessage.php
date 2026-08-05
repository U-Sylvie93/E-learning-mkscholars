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
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(CourseRoom::class, 'course_room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
