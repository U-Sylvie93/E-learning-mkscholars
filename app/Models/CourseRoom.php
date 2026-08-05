<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class CourseRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CourseRoomMessage::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(CourseRoomRead::class);
    }

    /**
     * True when the user is the course instructor or an active enrolled student.
     */
    public function hasMember(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $course = $this->course;
        if (! $course) {
            return false;
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return true;
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * All member users (instructor + active enrolled students).
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function memberUsers()
    {
        $course = $this->course;
        if (! $course) {
            return collect();
        }

        $studentIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->pluck('user_id');

        $ids = $studentIds->push($course->instructor_id)->filter()->unique()->values();

        return User::query()->whereIn('id', $ids)->get();
    }

    public function unreadCountFor(?User $user): int
    {
        if (! $user || ! Schema::hasTable('course_room_reads')) {
            return 0;
        }

        $read = $this->reads()->where('user_id', $user->id)->first();
        $lastReadAt = $read?->last_read_at;

        $query = $this->messages()->where('sender_id', '!=', $user->id);
        if ($lastReadAt) {
            $query->where('created_at', '>', $lastReadAt);
        }

        return $query->count();
    }

    public function markReadFor(?User $user): void
    {
        if (! $user || ! Schema::hasTable('course_room_reads')) {
            return;
        }

        CourseRoomRead::updateOrCreate(
            ['course_room_id' => $this->id, 'user_id' => $user->id],
            ['last_read_at' => now()],
        );
    }

    /**
     * Sum of unread messages across every room the given user belongs to.
     */
    public static function totalUnreadFor(?User $user): int
    {
        if (! $user || ! Schema::hasTable('course_rooms')) {
            return 0;
        }

        // Rooms user has access to: courses they instruct + courses they are actively enrolled in.
        $enrolledCourseIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->pluck('course_id');

        $instructedCourseIds = Course::query()
            ->where('instructor_id', $user->id)
            ->pluck('id');

        $courseIds = $enrolledCourseIds->merge($instructedCourseIds)->unique()->values();

        if ($courseIds->isEmpty()) {
            return 0;
        }

        $total = 0;
        static::query()
            ->whereIn('course_id', $courseIds)
            ->get()
            ->each(function (CourseRoom $room) use ($user, &$total): void {
                $total += $room->unreadCountFor($user);
            });

        return $total;
    }
}
