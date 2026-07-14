<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\CourseCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorCourseFormPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_course_form_marks_required_fields_and_not_optional_level_duration(): void
    {
        $instructor = $this->instructor('form-polish');
        $this->academy('form-polish');

        $this->actingAs($instructor)
            ->get(route('instructor.courses.create'))
            ->assertOk()
            ->assertSee('Academy <span class="text-red-600"', false)
            ->assertSee('Title <span class="text-red-600"', false)
            ->assertSee('Short description <span class="text-red-600"', false)
            ->assertSee('Status <span class="text-red-600"', false)
            ->assertSee('Access type <span class="text-red-600"', false)
            ->assertSee('placeholder="Optional"', false)
            ->assertDontSee('Level <span class="text-red-600"', false)
            ->assertDontSee('Duration <span class="text-red-600"', false);
    }

    public function test_instructor_course_form_shows_clear_overview_toolbar_controls(): void
    {
        $instructor = $this->instructor('overview-toolbar');
        $this->academy('overview-toolbar');

        $this->actingAs($instructor)
            ->get(route('instructor.courses.create'))
            ->assertOk()
            ->assertSee('data-markdown-toolbar="course-overview-input"', false)
            ->assertSee('aria-label="Bold"', false)
            ->assertSee('aria-label="Strikethrough"', false)
            ->assertSee('aria-label="Table"', false)
            ->assertSee('aria-label="Image"', false)
            ->assertSee('aria-label="Undo"', false)
            ->assertSee('aria-label="Redo"', false)
            ->assertSee('data-action="undo"', false)
            ->assertSee('data-action="redo"', false);
    }

    public function test_instructor_can_create_course_without_level_duration_and_slug_is_generated(): void
    {
        $instructor = $this->instructor('optional-fields');
        $academy = $this->academy('optional-fields');

        $this->actingAs($instructor)
            ->post(route('instructor.courses.store'), [
                'academy_id' => $academy->id,
                'title' => 'Introduction to JavaScript',
                'slug' => '',
                'short_description' => 'A practical JavaScript course.',
                'full_description' => "# Overview\n\n| Topic | Detail |\n| --- | --- |\n| Code | `let x = 1;` |\n\n![Diagram](https://example.com/diagram.png)",
                'level' => '',
                'duration' => '',
                'access_type' => Course::ACCESS_FREE,
                'offers_certificate' => '1',
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $course = Course::query()->where('slug', 'introduction-to-javascript')->firstOrFail();

        $this->assertNull($course->level);
        $this->assertNull($course->duration);
        $this->assertTrue($course->offersCertificate());
        $this->assertStringContainsString('| Topic | Detail |', $course->full_description);
    }

    public function test_duplicate_instructor_course_title_gets_unique_slug_suffix(): void
    {
        $instructor = $this->instructor('duplicate-slug');
        $academy = $this->academy('duplicate-slug');

        Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Introduction to JavaScript',
            'slug' => 'introduction-to-javascript',
            'short_description' => 'Existing course.',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_DRAFT,
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.courses.store'), [
                'academy_id' => $academy->id,
                'title' => 'Introduction to JavaScript',
                'slug' => '',
                'short_description' => 'Another course.',
                'access_type' => Course::ACCESS_FREE,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'slug' => 'introduction-to-javascript-2',
        ]);
    }

    public function test_certificate_tags_and_generation_respect_course_toggle(): void
    {
        $student = User::create([
            'name' => 'Student certificate toggle',
            'email' => 'student-certificate-toggle@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        $withoutCertificate = $this->publishedCourse('no-certificate-course', false);
        $withCertificate = $this->publishedCourse('certificate-course', true);

        $this->completeCourse($student, $withoutCertificate);
        $this->completeCourse($student, $withCertificate);

        app(CourseCompletionService::class)->calculate($student, $withoutCertificate);
        app(CourseCompletionService::class)->calculate($student, $withCertificate);

        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'course_id' => $withoutCertificate->id,
        ]);
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'course_id' => $withCertificate->id,
            'status' => Certificate::STATUS_PENDING,
        ]);

        $this->get(route('courses.show', $withoutCertificate->slug))
            ->assertOk()
            ->assertSee('No Certificate Course')
            ->assertDontSee('Certificate</span>', false)
            ->assertDontSee('Will I get a certificate?');

        $this->get(route('courses.show', $withCertificate->slug))
            ->assertOk()
            ->assertSee('Certificate Course')
            ->assertSee('Certificate</span>', false)
            ->assertSee('Will I get a certificate?');
    }

    private function completeCourse(User $student, Course $course): void
    {
        $lesson = $course->modules()->first()->lessons()->first();

        LessonProgress::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    private function publishedCourse(string $slug, bool $offersCertificate): Course
    {
        $course = Course::create([
            'academy_id' => $this->academy($slug)->id,
            'title' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'short_description' => 'Published course.',
            'full_description' => 'Published course overview.',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'offers_certificate' => $offersCertificate,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson '.$slug,
            'slug' => 'lesson-'.$slug,
            'lesson_type' => 'text',
            'content' => 'Lesson body.',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return $course;
    }

    private function instructor(string $slug): User
    {
        return User::create([
            'name' => 'Instructor '.$slug,
            'email' => $slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function academy(string $slug): Academy
    {
        return Academy::firstOrCreate(
            ['slug' => 'academy-'.$slug],
            [
                'name' => 'Academy '.$slug,
                'summary' => 'Summary',
                'description' => 'Description',
                'status' => Academy::STATUS_PUBLISHED,
            ],
        );
    }
}
