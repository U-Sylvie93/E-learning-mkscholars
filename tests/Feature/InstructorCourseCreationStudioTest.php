<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorCourseCreationStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_instructor_can_open_modern_course_creation_studio(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'studio-instructor@example.test');
        Academy::create([
            'name' => 'Studio Academy',
            'slug' => 'studio-academy',
            'summary' => 'Studio summary',
            'description' => 'Studio description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.create'))
            ->assertOk()
            ->assertSee('Instructor course studio')
            ->assertSee('Course cover image')
            ->assertSee('Full course overview')
            ->assertSee('Save &amp; Continue to Builder', false)
            ->assertDontSee('Public footer');
    }

    public function test_pending_instructor_cannot_open_course_creation_studio(): void
    {
        $pendingInstructor = $this->user(User::ROLE_INSTRUCTOR, 'pending-studio@example.test', User::APPROVAL_PENDING);

        $this->actingAs($pendingInstructor)
            ->get(route('instructor.courses.create'))
            ->assertRedirect(route('login'));
    }

    public function test_instructor_can_create_course_with_cover_image(): void
    {
        Storage::fake('public');

        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'studio-upload@example.test');
        $academy = Academy::create([
            'name' => 'Upload Academy',
            'slug' => 'upload-academy',
            'summary' => 'Upload summary',
            'description' => 'Upload description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.courses.store'), [
                'academy_id' => $academy->id,
                'title' => 'Image Studio Course',
                'slug' => 'image-studio-course',
                'short_description' => 'A course with an instructor uploaded image.',
                'full_description' => "# Course overview\n\nStudents learn with a polished path.",
                'featured_image' => UploadedFile::fake()->image('course-cover.jpg')->size(512),
                'level' => 'Beginner',
                'duration' => '4 weeks',
                'access_type' => Course::ACCESS_FREE,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
                'learning_outcomes' => "Upload images safely\nBuild course profiles",
            ])
            ->assertRedirect();

        $course = Course::query()->where('slug', 'image-studio-course')->firstOrFail();

        $this->assertSame($instructor->id, $course->instructor_id);
        $this->assertNotNull($course->featured_image_path);
        $this->assertStringStartsWith('courses/', $course->featured_image_path);
        Storage::disk('public')->assertExists($course->featured_image_path);
        $this->assertSame(['Upload images safely', 'Build course profiles'], $course->learning_outcomes);
    }

    public function test_instructor_image_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'studio-invalid-upload@example.test');
        $academy = Academy::create([
            'name' => 'Invalid Upload Academy',
            'slug' => 'invalid-upload-academy',
            'summary' => 'Upload summary',
            'description' => 'Upload description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->actingAs($instructor)
            ->from(route('instructor.courses.create'))
            ->post(route('instructor.courses.store'), [
                'academy_id' => $academy->id,
                'title' => 'Invalid Image Course',
                'slug' => 'invalid-image-course',
                'short_description' => 'A course with invalid image.',
                'featured_image' => UploadedFile::fake()->create('course-cover.pdf', 100, 'application/pdf'),
                'level' => 'Beginner',
                'duration' => '4 weeks',
                'access_type' => Course::ACCESS_FREE,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertRedirect(route('instructor.courses.create'))
            ->assertSessionHasErrors('featured_image');
    }

    public function test_instructor_can_replace_owned_course_image_without_editing_others(): void
    {
        Storage::fake('public');

        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'studio-replace@example.test');
        $otherInstructor = $this->user(User::ROLE_INSTRUCTOR, 'studio-other@example.test');
        $ownedCourse = $this->course('Owned Studio Course', 'owned-studio-course', $instructor);
        $otherCourse = $this->course('Other Studio Course', 'other-studio-course', $otherInstructor);

        $this->actingAs($instructor)
            ->put(route('instructor.courses.update', $ownedCourse), [
                'academy_id' => $ownedCourse->academy_id,
                'title' => $ownedCourse->title,
                'slug' => $ownedCourse->slug,
                'short_description' => $ownedCourse->short_description,
                'full_description' => $ownedCourse->full_description,
                'featured_image' => UploadedFile::fake()->image('replacement.png')->size(512),
                'level' => $ownedCourse->level,
                'duration' => $ownedCourse->duration,
                'access_type' => Course::ACCESS_FREE,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $ownedCourse->refresh();
        $this->assertNotNull($ownedCourse->featured_image_path);
        Storage::disk('public')->assertExists($ownedCourse->featured_image_path);

        $this->actingAs($instructor)
            ->put(route('instructor.courses.update', $otherCourse), [
                'academy_id' => $otherCourse->academy_id,
                'title' => $otherCourse->title,
                'slug' => $otherCourse->slug,
                'short_description' => $otherCourse->short_description,
                'level' => $otherCourse->level,
                'duration' => $otherCourse->duration,
                'access_type' => Course::ACCESS_FREE,
                'currency' => 'RWF',
                'status' => Course::STATUS_DRAFT,
            ])
            ->assertForbidden();
    }

    private function user(string $role, string $email, ?string $approvalStatus = User::APPROVAL_APPROVED): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => $approvalStatus,
            'approved_at' => $approvalStatus === User::APPROVAL_APPROVED ? now() : null,
        ]);
    }

    private function course(string $title, string $slug, User $instructor): Course
    {
        $academy = Academy::create([
            'name' => $title.' Academy',
            'slug' => $slug.'-academy',
            'summary' => 'Academy summary',
            'description' => 'Academy description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        return Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => $title,
            'slug' => $slug,
            'short_description' => 'Studio course summary',
            'full_description' => 'Studio course overview',
            'level' => 'Beginner',
            'duration' => '4 weeks',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'currency' => 'RWF',
            'status' => Course::STATUS_DRAFT,
        ]);
    }
}
