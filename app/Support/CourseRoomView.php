<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseRoom;
use App\Models\CourseRoomMessage;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseRoomView
{
    /**
     * Build the sidebar-row description for a course room from the given user's perspective.
     *
     * @return array{
     *   course_id:int,
     *   course_title:string,
     *   academy:?string,
     *   instructor_name:?string,
     *   last_message:?string,
     *   last_message_at:?\Illuminate\Support\Carbon,
     *   last_message_sender:?string,
     *   unread:int,
     * }
     */
    public static function describeRoom(Course $course, ?User $user): array
    {
        $room = $course->room;
        $lastMessage = null;
        $lastMessageAt = null;
        $lastMessageSender = null;
        $unread = 0;

        if ($room) {
            $latest = $room->messages()->with('sender:id,name')->latest()->first();
            if ($latest) {
                $lastMessage = $latest->body;
                $lastMessageAt = $latest->created_at;
                $lastMessageSender = $latest->sender?->name;
            }
            $unread = $user ? $room->unreadCountFor($user) : 0;
        }

        return [
            'course_id' => (int) $course->id,
            'course_title' => (string) $course->title,
            'academy' => $course->academy?->name,
            'instructor_name' => $course->instructor?->name,
            'last_message' => $lastMessage,
            'last_message_at' => $lastMessageAt,
            'last_message_sender' => $lastMessageSender,
            'unread' => (int) $unread,
        ];
    }

    /**
     * Package the currently-open room for the chat pane.
     */
    public static function describeActiveRoom(Course $course, CourseRoom $room, ?User $user): array
    {
        $messages = $room->messages()
            ->with('sender:id,name,role')
            ->orderBy('created_at')
            ->get();

        return [
            'course' => [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'academy' => $course->academy?->name,
                'instructor_name' => $course->instructor?->name,
            ],
            'room' => $room,
            'messages' => $messages,
            'my_id' => $user?->id,
        ];
    }

    /**
     * Validate the request body, create the message, bump last_message_at,
     * mark the sender as up-to-date and notify all other members.
     */
    public static function sendMessage(Request $request, Course $course, User $sender): CourseRoomMessage
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $room = CourseRoom::firstOrCreate(['course_id' => $course->id]);

        $message = $room->messages()->create([
            'sender_id' => $sender->id,
            'body' => $validated['body'],
        ]);

        $room->update(['last_message_at' => now()]);
        $room->markReadFor($sender);

        $recipients = $room->memberUsers()->filter(fn ($u) => (int) $u->id !== (int) $sender->id);
        $service = app(AppNotificationService::class);
        $courseUrlByRole = [
            User::ROLE_INSTRUCTOR => route('instructor.messages.show', $course),
            User::ROLE_STUDENT => route('student.messages.show', $course),
        ];

        foreach ($recipients as $recipient) {
            $url = $courseUrlByRole[$recipient->role] ?? null;
            try {
                $service->createForUser($recipient, [
                    'title' => 'New message in '.$course->title,
                    'message' => (string) Str::of($sender->name.': '.$validated['body'])->limit(140),
                    'action_url' => $url,
                    'role' => $recipient->role,
                ]);
            } catch (\Throwable $e) {
                // notifications are best-effort; don't break the send
            }
        }

        return $message;
    }
}
