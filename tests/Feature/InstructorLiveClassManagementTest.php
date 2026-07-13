<?php

namespace Tests\Feature;

use App\Filament\Resources\LiveClasses\LiveClassResource;
use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorLiveClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_live_class_for_own_course(): void
    {
        [$instructor, $course] = $this->instructorWithCourse('own-live-class');

        $this->actingAs($instructor)
            ->post(route('instructor.live-classes.store'), $this->validLiveClassPayload($course, [
                'title' => 'Weekly Strategy Session',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('live_classes', [
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Weekly Strategy Session',
            'platform' => LiveClass::PLATFORM_ZOOM,
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);
    }

    public function test_instructor_can_edit_own_live_class(): void
    {
        [$instructor, $course] = $this->instructorWithCourse('edit-live-class');
        $liveClass = $this->liveClass($instructor, $course);

        $this->actingAs($instructor)
            ->put(route('instructor.live-classes.update', $liveClass), $this->validLiveClassPayload($course, [
                'title' => 'Updated Session',
                'status' => LiveClass::STATUS_COMPLETED,
                'recording_url' => 'https://example.test/recording',
            ]))
            ->assertRedirect(route('instructor.live-classes.edit', $liveClass));

        $this->assertDatabaseHas('live_classes', [
            'id' => $liveClass->id,
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Updated Session',
            'status' => LiveClass::STATUS_COMPLETED,
            'recording_url' => 'https://example.test/recording',
        ]);
    }

    public function test_instructor_cannot_create_live_class_for_another_instructors_course(): void
    {
        [$instructor] = $this->instructorWithCourse('creator-course');
        [, $otherCourse] = $this->instructorWithCourse('other-course');

        $this->actingAs($instructor)
            ->post(route('instructor.live-classes.store'), $this->validLiveClassPayload($otherCourse))
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseMissing('live_classes', [
            'course_id' => $otherCourse->id,
            'instructor_id' => $instructor->id,
        ]);
    }

    public function test_instructor_cannot_edit_another_instructors_live_class(): void
    {
        [$instructor, $course] = $this->instructorWithCourse('own-edit-block');
        [$otherInstructor, $otherCourse] = $this->instructorWithCourse('other-edit-block');
        $liveClass = $this->liveClass($otherInstructor, $otherCourse);

        $this->actingAs($instructor)
            ->put(route('instructor.live-classes.update', $liveClass), $this->validLiveClassPayload($course))
            ->assertForbidden();
    }

    public function test_student_and_guest_cannot_create_live_classes(): void
    {
        [, $course] = $this->instructorWithCourse('blocked-live-class');
        $student = User::create([
            'name' => 'Student',
            'email' => 'student-live-block@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->post(route('instructor.live-classes.store'), $this->validLiveClassPayload($course))
            ->assertRedirect(route('login'));

        $this->actingAs($student)
            ->post(route('instructor.live-classes.store'), $this->validLiveClassPayload($course))
            ->assertForbidden();
    }

    public function test_live_class_validation_requires_core_fields_and_valid_times_and_urls(): void
    {
        [$instructor, $course] = $this->instructorWithCourse('validation-live-class');

        $this->actingAs($instructor)
            ->post(route('instructor.live-classes.store'), [])
            ->assertSessionHasErrors(['course_id', 'title', 'meeting_url', 'platform', 'starts_at', 'ends_at', 'status']);

        $this->actingAs($instructor)
            ->post(route('instructor.live-classes.store'), $this->validLiveClassPayload($course, [
                'starts_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'meeting_url' => 'not-a-url',
                'recording_url' => 'not-a-url',
            ]))
            ->assertSessionHasErrors(['ends_at', 'meeting_url', 'recording_url']);
    }

    public function test_student_can_view_live_classes_for_accessible_course_only(): void
    {
        [$instructor, $accessibleCourse] = $this->instructorWithCourse('accessible-live-class');
        [, $inaccessibleCourse] = $this->instructorWithCourse('hidden-live-class');
        $student = User::create([
            'name' => 'Live Student',
            'email' => 'live-student@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $accessibleCourse->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
        $visibleLiveClass = $this->liveClass($instructor, $accessibleCourse, [
            'title' => 'Visible Live Class',
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => LiveClass::STATUS_LIVE,
        ]);
        $this->liveClass($instructor, $inaccessibleCourse, ['title' => 'Hidden Live Class']);

        $this->actingAs($student)
            ->get(route('student.live-classes'))
            ->assertOk()
            ->assertSee('Visible Live Class')
            ->assertDontSee('Hidden Live Class');

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $visibleLiveClass))
            ->assertRedirect($visibleLiveClass->meeting_url);
    }

    public function test_student_cannot_view_live_class_links_for_inaccessible_course(): void
    {
        [$instructor, $course] = $this->instructorWithCourse('inaccessible-join-class');
        $student = User::create([
            'name' => 'Blocked Student',
            'email' => 'blocked-live-student@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        $liveClass = $this->liveClass($instructor, $course);

        $this->actingAs($student)
            ->post(route('student.live-classes.join', $liveClass))
            ->assertSessionHasErrors(['live_class' => 'You do not have access to this class.']);
    }

    public function test_admin_live_class_resource_still_uses_live_class_model(): void
    {
        $this->assertSame(LiveClass::class, LiveClassResource::getModel());
    }

    private function validLiveClassPayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $course->id,
            'title' => 'Live Class',
            'description' => 'Class description',
            'meeting_url' => 'https://example.test/meeting',
            'platform' => LiveClass::PLATFORM_ZOOM,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'status' => LiveClass::STATUS_SCHEDULED,
            'recording_url' => null,
        ], $overrides);
    }

    private function instructorWithCourse(string $slug): array
    {
        $instructor = User::create([
            'name' => 'Instructor '.$slug,
            'email' => $slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
        ]);
        $course = $this->course($slug, $instructor);

        return [$instructor, $course];
    }

    private function course(string $slug, User $instructor): Course
    {
        $academy = Academy::firstOrCreate(
            ['slug' => 'live-class-academy'],
            [
                'name' => 'Live Class Academy',
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
            'title' => 'Managed Live Class',
            'description' => 'A live session.',
            'meeting_url' => 'https://example.test/meeting',
            'platform' => LiveClass::PLATFORM_ZOOM,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => LiveClass::STATUS_SCHEDULED,
        ], $overrides));
    }
}
