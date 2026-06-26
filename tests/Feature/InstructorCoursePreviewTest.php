<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorCoursePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_access_dashboard(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-dashboard@mkscholars.test');

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Teaching command center');
    }

    public function test_guest_cannot_access_instructor_dashboard(): void
    {
        $this->get(route('instructor.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_instructor_dashboard(): void
    {
        $student = $this->user(User::ROLE_STUDENT, 'student-dashboard-block@mkscholars.test');

        $this->actingAs($student)
            ->get(route('instructor.dashboard'))
            ->assertForbidden();
    }

    public function test_mentor_cannot_access_instructor_dashboard(): void
    {
        $mentor = $this->user(User::ROLE_MENTOR, 'mentor-dashboard-block@mkscholars.test');

        $this->actingAs($mentor)
            ->get(route('instructor.dashboard'))
            ->assertForbidden();
    }

    public function test_instructor_sees_only_assigned_courses(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-courses@mkscholars.test');
        $assignedCourse = $this->course('Assigned Course', 'assigned-course');
        $unrelatedCourse = $this->course('Unrelated Course', 'unrelated-course');

        $this->assignInstructorToCourse($instructor, $assignedCourse);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk()
            ->assertSee('Assigned Course')
            ->assertDontSee('Unrelated Course');
    }

    public function test_instructor_cannot_access_unrelated_course_detail(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-detail-block@mkscholars.test');
        $assignedCourse = $this->course('Assigned Course', 'assigned-detail-course');
        $unrelatedCourse = $this->course('Unrelated Course', 'unrelated-detail-course');

        $this->assignInstructorToCourse($instructor, $assignedCourse);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.show', $unrelatedCourse))
            ->assertForbidden();
    }

    public function test_instructor_cannot_access_unrelated_course_students_page(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-students-block@mkscholars.test');
        $assignedCourse = $this->course('Assigned Course', 'assigned-students-course');
        $unrelatedCourse = $this->course('Unrelated Course', 'unrelated-students-course');

        $this->assignInstructorToCourse($instructor, $assignedCourse);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.students', $unrelatedCourse))
            ->assertForbidden();
    }

    public function test_instructor_cannot_access_unrelated_course_submissions_or_quiz_attempts(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-related-block@mkscholars.test');
        $assignedCourse = $this->course('Assigned Course', 'assigned-related-course');
        $unrelatedCourse = $this->course('Unrelated Course', 'unrelated-related-course');

        $this->assignInstructorToCourse($instructor, $assignedCourse);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.submissions', $unrelatedCourse))
            ->assertForbidden();

        $this->actingAs($instructor)
            ->get(route('instructor.courses.quiz-attempts', $unrelatedCourse))
            ->assertForbidden();
    }

    public function test_instructor_students_page_shows_assigned_course_enrollments(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-students@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'assigned-student@mkscholars.test');
        $course = $this->course('Student Preview Course', 'student-preview-course');

        $this->assignInstructorToCourse($instructor, $course);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.students', $course))
            ->assertOk()
            ->assertSee('assigned-student@mkscholars.test');
    }

    public function test_course_inference_works_from_module_and_lesson_live_classes(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-inference@mkscholars.test');
        $moduleCourse = $this->course('Module Linked Course', 'module-linked-course');
        $lessonCourse = $this->course('Lesson Linked Course', 'lesson-linked-course');

        $this->assignInstructorToCourse($instructor, $moduleCourse, 'module');
        $this->assignInstructorToCourse($instructor, $lessonCourse, 'lesson');

        $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk()
            ->assertSee('Module Linked Course')
            ->assertSee('Lesson Linked Course');
    }

    public function test_duplicate_inferred_courses_are_not_duplicated(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-duplicates@mkscholars.test');
        $course = $this->course('Duplicate Linked Course', 'duplicate-linked-course');

        $this->assignInstructorToCourse($instructor, $course);
        $this->assignInstructorToCourse($instructor, $course, 'module');
        $this->assignInstructorToCourse($instructor, $course, 'lesson');

        $response = $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'Duplicate Linked Course'));
    }

    public function test_missing_live_class_relationships_do_not_crash_instructor_pages(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'instructor-orphan-live@mkscholars.test');

        LiveClass::create([
            'instructor_id' => $instructor->id,
            'title' => 'Unlinked live session',
            'meeting_url' => 'https://example.test/orphan',
            'platform' => LiveClass::PLATFORM_OTHER,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk();

        $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk()
            ->assertSee('No assigned courses');

        $this->actingAs($instructor)
            ->get(route('instructor.live-classes.index'))
            ->assertOk()
            ->assertSee('Unlinked live session');
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function course(string $title, string $slug): Course
    {
        $academy = Academy::firstOrCreate(
            ['slug' => 'instructor-preview-academy'],
            [
                'name' => 'Instructor Preview Academy',
                'summary' => 'Demo',
                'description' => 'Demo',
                'status' => Academy::STATUS_PUBLISHED,
            ],
        );

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => $title,
            'slug' => $slug,
            'short_description' => 'Instructor preview course',
            'full_description' => 'Instructor preview course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => $title.' Module',
            'slug' => $slug.'-module',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Lesson::create([
            'module_id' => $module->id,
            'title' => $title.' Lesson',
            'slug' => $slug.'-lesson',
            'lesson_type' => 'text',
            'content' => 'Lesson content',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return $course;
    }

    private function assignInstructorToCourse(User $instructor, Course $course, string $linkType = 'course'): void
    {
        $module = $course->modules()->first();
        $lesson = $module?->lessons()->first();

        LiveClass::create([
            'course_id' => $linkType === 'course' ? $course->id : null,
            'module_id' => $linkType === 'module' ? $module?->id : null,
            'lesson_id' => $linkType === 'lesson' ? $lesson?->id : null,
            'instructor_id' => $instructor->id,
            'title' => 'Live session for '.$course->title,
            'meeting_url' => 'https://example.test/meeting',
            'platform' => LiveClass::PLATFORM_OTHER,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => LiveClass::STATUS_SCHEDULED,
        ]);
    }
}
