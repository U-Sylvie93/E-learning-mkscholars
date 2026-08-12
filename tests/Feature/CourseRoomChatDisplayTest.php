<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRoom;
use App\Models\CourseRoomMessage;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRoomChatDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_shell_has_mobile_safe_layout_and_delete_controls(): void
    {
        $view = str_replace("\r\n", "\n", file_get_contents(resource_path('views/partials/chat-shell.blade.php')));

        $this->assertStringContainsString('-mx-4 flex h-[calc(100dvh-7.25rem)]', $view);
        $this->assertStringContainsString('max-w-[min(88%,22rem)]', $view);
        $this->assertStringContainsString('[overflow-wrap:anywhere]', $view);
        $this->assertStringContainsString('aria-label="Delete message"', $view);
        $this->assertStringContainsString('This message was deleted', $view);
        $this->assertStringContainsString('placeholder="Type a message"', $view);
    }

    public function test_student_can_delete_own_course_room_message_only(): void
    {
        [$student, $otherStudent, $course, $room] = $this->chatContext();
        $myMessage = CourseRoomMessage::create([
            'course_room_id' => $room->id,
            'sender_id' => $student->id,
            'body' => 'Please remove this message',
        ]);
        $otherMessage = CourseRoomMessage::create([
            'course_room_id' => $room->id,
            'sender_id' => $otherStudent->id,
            'body' => 'Keep this message',
        ]);

        $this->actingAs($student)
            ->delete(route('student.messages.delete', [$course, $otherMessage]))
            ->assertForbidden();

        $this->actingAs($student)
            ->delete(route('student.messages.delete', [$course, $myMessage]))
            ->assertRedirect(route('student.messages.show', $course));

        $this->assertDatabaseHas('course_room_messages', [
            'id' => $myMessage->id,
            'body' => '',
            'deleted_by_id' => $student->id,
        ]);
        $this->assertNotNull($myMessage->fresh()->deleted_at);
        $this->assertDatabaseHas('course_room_messages', [
            'id' => $otherMessage->id,
            'body' => 'Keep this message',
            'deleted_by_id' => null,
        ]);
    }

    private function chatContext(): array
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'chat-instructor@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'chat-student@mkscholars.test');
        $otherStudent = $this->user(User::ROLE_STUDENT, 'chat-other-student@mkscholars.test');
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $room = CourseRoom::create(['course_id' => $course->id]);

        foreach ([$student, $otherStudent] as $user) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        return [$student, $otherStudent, $course, $room];
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline().' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
