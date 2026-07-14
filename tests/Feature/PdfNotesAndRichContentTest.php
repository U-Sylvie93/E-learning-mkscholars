<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfNotesAndRichContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_upload_pdf_lesson_material(): void
    {
        Storage::fake('public');
        [$student, $instructor, $course, $lesson] = $this->learningContext('pdf-upload');

        $this->actingAs($instructor)
            ->post(route('instructor.lesson-materials.store', $course), [
                'lesson_id' => $lesson->id,
                'title' => 'Lesson PDF Notes',
                'instructions' => 'Read before class.',
                'material_file' => UploadedFile::fake()->create('notes.pdf', 250, 'application/pdf'),
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $activity = LessonActivity::query()->where('lesson_id', $lesson->id)->firstOrFail();

        $this->assertTrue($activity->isPdfResource());
        $this->assertSame('public', $activity->resource_disk);
        Storage::disk('public')->assertExists($activity->resource_path);
    }

    public function test_invalid_lesson_material_file_type_is_rejected(): void
    {
        Storage::fake('public');
        [, $instructor, $course, $lesson] = $this->learningContext('invalid-material');

        $this->actingAs($instructor)
            ->from(route('instructor.courses.edit', $course))
            ->post(route('instructor.lesson-materials.store', $course), [
                'lesson_id' => $lesson->id,
                'title' => 'Unsafe Material',
                'material_file' => UploadedFile::fake()->create('unsafe.exe', 12, 'application/x-msdownload'),
                'status' => Course::STATUS_PUBLISHED,
            ])
            ->assertRedirect(route('instructor.courses.edit', $course))
            ->assertSessionHasErrors('material_file');
    }

    public function test_student_learning_page_embeds_pdf_viewer_for_uploaded_pdf(): void
    {
        Storage::fake('public');
        [$student, , $course, $lesson] = $this->learningContext('pdf-viewer');
        Storage::disk('public')->put('lesson-materials/notes.pdf', '%PDF-1.4 test');
        $activity = LessonActivity::create([
            'lesson_id' => $lesson->id,
            'activity_type' => 'download',
            'type' => 'material',
            'title' => 'Protected PDF Notes',
            'resource_path' => 'lesson-materials/notes.pdf',
            'resource_disk' => 'public',
            'resource_mime' => 'application/pdf',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee('data-testid="lesson-pdf-viewer"', false)
            ->assertSee(route('student.lesson-materials.view', $activity), false)
            ->assertDontSee('/storage/lesson-materials/notes.pdf', false);
    }

    public function test_pdf_view_route_is_inline_and_access_protected(): void
    {
        Storage::fake('public');
        [$student, , $course, $lesson] = $this->learningContext('protected-pdf');
        $outsider = User::create([
            'name' => 'Outsider',
            'email' => 'outsider-protected-pdf@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        Storage::disk('public')->put('lesson-materials/protected.pdf', '%PDF-1.4 protected');
        $activity = LessonActivity::create([
            'lesson_id' => $lesson->id,
            'activity_type' => 'download',
            'type' => 'material',
            'title' => 'Protected PDF',
            'resource_path' => 'lesson-materials/protected.pdf',
            'resource_disk' => 'public',
            'resource_mime' => 'application/pdf',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->get(route('student.lesson-materials.view', $activity))
            ->assertRedirect(route('login'));

        $this->actingAs($outsider)
            ->get(route('student.lesson-materials.view', $activity))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('student.lesson-materials.view', $activity))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="protected-pdf.pdf"');
    }

    public function test_rich_course_overview_renders_tables_code_and_images_responsively(): void
    {
        [, , $course] = $this->learningContext('rich-pdf-content');

        $course->update([
            'full_description' => <<<'MARKDOWN'
# Overview

| Week | Focus |
| --- | --- |
| 1 | Foundations |

```php
echo "hello";
```

![Diagram](https://example.com/diagram.png)
MARKDOWN,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('mk-rich-content', false)
            ->assertSee('mk-rich-table', false)
            ->assertSee('<code class="language-php">', false)
            ->assertSee('src="https://example.com/diagram.png"', false);
    }

    private function learningContext(string $slug): array
    {
        $academy = Academy::create([
            'name' => 'Academy '.$slug,
            'slug' => 'academy-'.$slug,
            'summary' => 'Summary',
            'description' => 'Description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $instructor = User::create([
            'name' => 'Instructor '.$slug,
            'email' => 'instructor-'.$slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
        $student = User::create([
            'name' => 'Student '.$slug,
            'email' => 'student-'.$slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Course summary.',
            'full_description' => 'Course overview.',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson '.$slug,
            'slug' => 'lesson-'.$slug,
            'lesson_type' => 'text',
            'content' => 'Lesson body.',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $instructor, $course, $lesson];
    }
}
