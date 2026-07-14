<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveClassSmartButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_upcoming_live_class_does_not_show_active_join_button_or_redirect(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:00:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('upcoming-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Upcoming')
            ->assertSee('Class starts soon')
            ->assertDontSee('Join Class');

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'Class has not started yet.']);
    }

    public function test_live_class_can_be_joined_at_exact_start_and_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:00:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('inclusive-time-window');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => Carbon::parse('2026-07-13 10:00:00'),
            'ends_at' => Carbon::parse('2026-07-13 11:00:00'),
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($liveClass->isLiveNow());

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertRedirect($liveClass->meeting_url);

        Carbon::setTestNow(Carbon::parse('2026-07-13 11:00:00'));

        $this->assertTrue($liveClass->fresh()->isLiveNow());

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertRedirect($liveClass->meeting_url);
    }

    public function test_scheduled_status_does_not_keep_current_class_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('scheduled-but-live');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);

        $this->assertSame('Live Now', $liveClass->displayStatus());

        $this->actingAs($instructor)
            ->get(route('instructor.live-classes.index'))
            ->assertOk()
            ->assertSee('Live Now')
            ->assertSee('Join Class')
            ->assertDontSee('Upcoming</span>', false);
    }

    public function test_missing_meeting_link_has_clear_join_error(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('missing-meeting-link');
        $liveClass = $this->liveClass($instructor, $course, [
            'meeting_url' => '',
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'Meeting link is not available.']);
    }

    public function test_live_class_during_time_shows_join_and_redirects_without_recording_button(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('live-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'recording_url' => 'https://example.test/recording',
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Live Now')
            ->assertSee('Join Class')
            ->assertDontSee('Watch Recording');

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertRedirect($liveClass->meeting_url);
    }

    public function test_ended_live_class_with_recording_shows_and_redirects_to_recording(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 12:00:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('recording-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'recording_url' => 'https://example.test/recording',
            'status' => LiveClass::STATUS_COMPLETED,
        ]);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Recording Available')
            ->assertSee('Watch Recording')
            ->assertDontSee('Join Class');

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'Class has ended.']);

        $this->actingAs($student)
            ->get(route('student.live-classes.recording', $liveClass))
            ->assertRedirect($liveClass->recording_url);
    }

    public function test_ended_live_class_without_recording_shows_recording_not_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 12:00:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('missing-recording-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'status' => LiveClass::STATUS_COMPLETED,
        ]);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Class Ended')
            ->assertSee('Recording Not Available')
            ->assertDontSee('Join Class');

        $this->actingAs($student)
            ->get(route('student.live-classes.recording', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'Recording is not available yet.']);
    }

    public function test_cancelled_live_class_has_no_active_join_or_recording_buttons(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('cancelled-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'recording_url' => 'https://example.test/recording',
            'status' => LiveClass::STATUS_CANCELLED,
        ]);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Cancelled')
            ->assertDontSee('Join Class')
            ->assertDontSee('Watch Recording');

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'This class has been cancelled.']);
    }

    public function test_guest_and_student_without_access_cannot_use_live_class_routes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('protected-smart-button');
        $outsider = $this->student('outsider-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
            'recording_url' => 'https://example.test/recording',
        ]);

        $this->post(route('student.live-classes.join', $liveClass))
            ->assertRedirect();

        $this->get(route('student.live-classes.recording', $liveClass))
            ->assertRedirect();

        $this->actingAs($outsider)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'You do not have access to this class.']);

        $this->actingAs($outsider)
            ->get(route('student.live-classes.recording', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'You do not have access to this class.']);
    }

    public function test_instructor_can_use_own_time_checked_live_class_links_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('instructor-smart-button');
        $otherInstructor = $this->instructor('other-instructor-smart-button');
        $liveClass = $this->liveClass($instructor, $course, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.live-classes.join', $liveClass))
            ->assertRedirect($liveClass->meeting_url);

        $this->actingAs($otherInstructor)
            ->get(route('instructor.live-classes.join', $liveClass))
            ->assertForbidden();
    }

    public function test_button_labels_remain_correct_on_student_learning_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 10:30:00'));
        [$student, $instructor, $course] = $this->enrolledStudentWithCourse('learning-smart-button');
        $this->liveClass($instructor, $course, [
            'title' => 'Learning Page Live Class',
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk()
            ->assertSee('Learning Page Live Class')
            ->assertSee('Live Now')
            ->assertSee('Join Class');
    }

    private function enrolledStudentWithCourse(string $slug): array
    {
        $instructor = $this->instructor($slug);
        $student = $this->student($slug);
        $course = $this->course($slug, $instructor);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $instructor, $course];
    }

    private function instructor(string $slug): User
    {
        return User::create([
            'name' => 'Instructor '.$slug,
            'email' => $slug.'-instructor@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
        ]);
    }

    private function student(string $slug): User
    {
        return User::create([
            'name' => 'Student '.$slug,
            'email' => $slug.'-student@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
    }

    private function course(string $slug, User $instructor): Course
    {
        $academy = Academy::firstOrCreate(
            ['slug' => 'smart-live-class-academy'],
            [
                'name' => 'Smart Live Class Academy',
                'summary' => 'Demo',
                'description' => 'Demo',
                'status' => Academy::STATUS_PUBLISHED,
            ],
        );

        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Live class course',
            'full_description' => 'Live class course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        Module::create([
            'course_id' => $course->id,
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return $course;
    }

    private function liveClass(User $instructor, Course $course, array $overrides = []): LiveClass
    {
        return LiveClass::create(array_merge([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Smart Live Class',
            'description' => 'A live session.',
            'meeting_url' => 'https://example.test/meeting',
            'platform' => LiveClass::PLATFORM_ZOOM,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'status' => LiveClass::STATUS_SCHEDULED,
            'recording_url' => null,
        ], $overrides));
    }
}
