<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseRoom;
use App\Models\CourseRoomMessage;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $lastMessage = null;
        $lastMessageAt = null;
        $lastMessageSender = null;
        $unread = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('course_rooms')) {
                $room = $course->room;

                if ($room) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('course_room_messages')) {
                        $latest = $room->messages()->with('sender:id,name')->latest()->first();
                        if ($latest) {
                            $lastMessage = $latest->isDeleted() ? 'This message was deleted' : trim((string) $latest->body);
                            if ($lastMessage === '' && method_exists($latest, 'hasAttachment') && $latest->hasAttachment()) {
                                $lastMessage = $latest->isImageAttachment() ? '📷 Photo' : '📎 '.($latest->attachment_name ?: 'File');
                            }
                            $lastMessageAt = $latest->created_at;
                            $lastMessageSender = $latest->sender?->name;
                        }
                    }
                    $unread = $user ? $room->unreadCountFor($user) : 0;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('describeRoom failed: '.$e->getMessage(), ['course_id' => $course->id]);
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
        $messages = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('course_room_messages')) {
                $messages = $room->messages()
                    ->with('sender:id,name,role')
                    ->orderBy('created_at')
                    ->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('describeActiveRoom failed: '.$e->getMessage());
        }

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
            'body' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip'],
        ]);

        if (empty(trim((string) ($validated['body'] ?? ''))) && ! $request->hasFile('attachment')) {
            abort(422, 'Message body or an attachment is required.');
        }

        $room = CourseRoom::firstOrCreate(['course_id' => $course->id]);

        $attachmentData = [];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments', 'public');
            $attachmentData = [
                'attachment_path' => $path,
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        $message = $room->messages()->create(array_merge([
            'sender_id' => $sender->id,
            'body' => trim((string) ($validated['body'] ?? '')),
        ], $attachmentData));

        $room->update(['last_message_at' => now()]);
        $room->markReadFor($sender);

        $recipients = $room->memberUsers()->filter(fn ($u) => (int) $u->id !== (int) $sender->id);
        $service = app(AppNotificationService::class);
        $courseUrlByRole = [
            User::ROLE_INSTRUCTOR => route('instructor.messages.show', $course),
            User::ROLE_STUDENT => route('student.messages.show', $course),
        ];

        $bodyText = trim((string) ($validated['body'] ?? ''));
        $notificationBody = $bodyText !== ''
            ? $sender->name.': '.$bodyText
            : $sender->name.' sent an attachment';

        foreach ($recipients as $recipient) {
            $url = $courseUrlByRole[$recipient->role] ?? null;
            try {
                $service->createForUser($recipient, [
                    'title' => 'New message in '.$course->title,
                    'message' => (string) Str::of($notificationBody)->limit(140),
                    'action_url' => $url,
                    'role' => $recipient->role,
                ]);
            } catch (\Throwable $e) {
                // notifications are best-effort; don't break the send
            }
        }

        return $message;
    }

    public static function deleteMessage(CourseRoomMessage $message, User $user): void
    {
        abort_unless((int) $message->sender_id === (int) $user->id, 403);

        $room = $message->room;
        $attachmentPath = $message->attachment_path;

        if ($message->hasAttachment()) {
            Storage::disk('public')->delete($attachmentPath);
        }

        $message->forceFill([
            'body' => '',
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'deleted_at' => now(),
            'deleted_by_id' => $user->id,
        ])->save();

        if ($room) {
            $room->update(['last_message_at' => now()]);
        }
    }
}
