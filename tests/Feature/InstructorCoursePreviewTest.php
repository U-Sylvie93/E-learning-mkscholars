<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\Quiz;
use App\Models\AssignmentQuestion;
use App\Models\Assignment;
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


    public function test_instructor_can_create_owned_course_draft(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'builder-owner@mkscholars.test');
        $academy = Academy::firstOrCreate(
            ['slug' => 'builder-academy'],
            [
                'name' => 'Builder Academy',
                'summary' => 'Demo',
                'description' => 'Demo',
                'status' => Academy::STATUS_PUBLISHED,
            ],
        );

        $response = $this->actingAs($instructor)->post(route('instructor.courses.store'), [
            'academy_id' => $academy->id,
            'title' => 'Instructor Built Course',
            'slug' => 'instructor-built-course',
            'short_description' => 'A course created by an instructor.',
            'full_description' => 'Detailed overview.',
            'level' => 'Beginner',
            'duration' => '3 weeks',
            'access_type' => Course::ACCESS_FREE,
            'currency' => 'RWF',
            'status' => Course::STATUS_DRAFT,
            'learning_outcomes' => "Build safely\nTeach clearly",
        ]);

        $course = Course::query()->where('slug', 'instructor-built-course')->first();

        $this->assertNotNull($course);
        $this->assertSame($instructor->id, $course->instructor_id);
        $this->assertSame(['Build safely', 'Teach clearly'], $course->learning_outcomes);
        $response->assertRedirect(route('instructor.courses.edit', $course));
    }

    public function test_owned_courses_appear_without_live_class_assignment(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'owned-course-list@mkscholars.test');
        $ownedCourse = $this->course('Owned Course', 'owned-course-list');
        $ownedCourse->update(['instructor_id' => $instructor->id]);
        $unrelatedCourse = $this->course('Unowned Course', 'unowned-course-list');

        $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk()
            ->assertSee('Owned Course')
            ->assertSee('Builder')
            ->assertDontSee('Unowned Course');
    }

    public function test_instructor_can_edit_only_owned_course(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'owner-edit@mkscholars.test');
        $otherInstructor = $this->user(User::ROLE_INSTRUCTOR, 'other-owner-edit@mkscholars.test');
        $ownedCourse = $this->course('Editable Course', 'editable-course');
        $ownedCourse->update(['instructor_id' => $instructor->id]);
        $otherCourse = $this->course('Other Editable Course', 'other-editable-course');
        $otherCourse->update(['instructor_id' => $otherInstructor->id]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.edit', $ownedCourse))
            ->assertOk()
            ->assertSee('Course profile and builder');

        $this->actingAs($instructor)
            ->get(route('instructor.courses.edit', $otherCourse))
            ->assertForbidden();
    }

    public function test_instructor_builder_creates_modules_lessons_quizzes_and_assignments_for_owned_course(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'builder-content@mkscholars.test');
        $course = $this->course('Builder Content Course', 'builder-content-course');
        $course->update(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store', $course), [
                'title' => 'Builder Module',
                'summary' => 'Module summary',
                'sort_order' => 1,
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $module = Module::query()->where('course_id', $course->id)->where('title', 'Builder Module')->first();
        $this->assertNotNull($module);

        $this->actingAs($instructor)
            ->post(route('instructor.lessons.store', $course), [
                'module_id' => $module->id,
                'title' => 'Builder Lesson',
                'lesson_type' => 'video',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'content' => 'Builder lesson content.',
                'duration_minutes' => 12,
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $lesson = Lesson::query()->where('module_id', $module->id)->where('title', 'Builder Lesson')->first();
        $this->assertNotNull($lesson);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store', $course), [
                'lesson_id' => $lesson->id,
                'title' => 'Builder Quiz',
                'passing_score' => 70,
                'status' => Quiz::STATUS_PUBLISHED,
                'question_text' => 'What does the event loop schedule?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'option_a' => 'Tasks',
                'option_b' => 'Paint only',
                'correct_option' => 'a',
            ])
            ->assertRedirect();

        $quiz = Quiz::query()->where('lesson_id', $lesson->id)->where('title', 'Builder Quiz')->first();
        $this->assertNotNull($quiz);
        $this->assertDatabaseHas('quiz_options', ['option_text' => 'Tasks', 'is_correct' => true]);

        $this->actingAs($instructor)
            ->post(route('instructor.assignments.store', $course), [
                'lesson_id' => $lesson->id,
                'title' => 'Builder Assignment',
                'instructions' => 'Explain the event loop.',
                'submission_type' => Assignment::TYPE_MIXED,
                'max_score' => 100,
                'allow_late_submission' => 1,
                'status' => Assignment::STATUS_PUBLISHED,
                'question_text' => 'Describe one event loop phase.',
                'question_type' => AssignmentQuestion::TYPE_TEXTAREA,
            ])
            ->assertRedirect();

        $assignment = Assignment::query()->where('lesson_id', $lesson->id)->where('title', 'Builder Assignment')->first();
        $this->assertNotNull($assignment);
        $this->assertDatabaseHas('assignment_questions', ['assignment_id' => $assignment->id, 'question_text' => 'Describe one event loop phase.']);
    }


    public function test_instructor_created_lessons_get_automatic_unique_slugs(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'lesson-slugs@mkscholars.test');
        $course = $this->course('Slug Builder Course', 'slug-builder-course');
        $course->update(['instructor_id' => $instructor->id]);
        $module = $course->modules()->first();

        $this->actingAs($instructor)
            ->post(route('instructor.lessons.store', $course), [
                'module_id' => $module->id,
                'title' => 'Reusable Lesson Title',
                'lesson_type' => 'text',
                'content' => 'Reading lesson content.',
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $this->actingAs($instructor)
            ->post(route('instructor.lessons.store', $course), [
                'module_id' => $module->id,
                'title' => 'Reusable Lesson Title',
                'lesson_type' => 'video',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $this->actingAs($instructor)
            ->post(route('instructor.lessons.store', $course), [
                'module_id' => $module->id,
                'title' => 'Manual Slug Lesson',
                'slug' => 'custom-instructor-lesson',
                'lesson_type' => 'text',
                'content' => 'Manual slug lesson content.',
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $createdLessons = Lesson::query()
            ->where('module_id', $module->id)
            ->whereIn('title', ['Reusable Lesson Title', 'Manual Slug Lesson'])
            ->orderBy('id')
            ->get();

        $this->assertSame('reusable-lesson-title', $createdLessons->get(0)?->slug);
        $this->assertSame('reusable-lesson-title-2', $createdLessons->get(1)?->slug);
        $this->assertSame('custom-instructor-lesson', $createdLessons->get(2)?->slug);
    }

    public function test_instructor_course_builder_completion_summary_shows_final_test_status(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'completion-summary@mkscholars.test');
        $course = $this->course('Completion Summary Course', 'completion-summary-course');
        $course->update(['instructor_id' => $instructor->id]);
        $module = $course->modules()->first();
        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Summary Video Lesson',
            'slug' => 'summary-video-lesson',
            'lesson_type' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => 'Published Final Test',
            'passing_score' => 70,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.edit', $course))
            ->assertOk()
            ->assertSee('Course completion summary')
            ->assertSee('Videos')
            ->assertSee('Reading')
            ->assertSee('Quizzes')
            ->assertSee('Assignments')
            ->assertSee('Final Test')
            ->assertSee('Published Final Test')
            ->assertSee('Published');
    }    public function test_instructor_cannot_build_content_for_unowned_course(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'builder-blocked@mkscholars.test');
        $course = $this->course('Blocked Builder Course', 'blocked-builder-course');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store', $course), [
                'title' => 'Blocked Module',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertForbidden();
    }

    public function test_students_and_mentors_cannot_open_instructor_course_builder(): void
    {
        $student = $this->user(User::ROLE_STUDENT, 'student-builder-block@mkscholars.test');
        $mentor = $this->user(User::ROLE_MENTOR, 'mentor-builder-block@mkscholars.test');

        $this->actingAs($student)
            ->get(route('instructor.courses.create'))
            ->assertForbidden();

        $this->actingAs($mentor)
            ->get(route('instructor.courses.create'))
            ->assertForbidden();
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


