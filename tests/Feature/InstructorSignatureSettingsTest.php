<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorSignatureSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_settings_page_shows_certificate_signature_section(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-settings-instructor@mkscholars.test');

        $this->actingAs($instructor)
            ->get(route('instructor.settings'))
            ->assertOk()
            ->assertSee('Certificate Signature')
            ->assertSee('Upload a clear PNG, JPG, JPEG, or WebP image of your signature.');
    }

    public function test_instructor_can_upload_supported_signature_images(): void
    {
        foreach ([
            ['png', 'image/png'],
            ['jpg', 'image/jpeg'],
            ['jpeg', 'image/jpeg'],
            ['webp', 'image/webp'],
        ] as [$extension, $mime]) {
            Storage::fake('public');
            $instructor = $this->user(User::ROLE_INSTRUCTOR, "signature-{$extension}@mkscholars.test");

            $this->actingAs($instructor)
                ->post(route('instructor.settings.signature'), [
                    'signature' => UploadedFile::fake()->create("signature.{$extension}", 128, $mime),
                ])
                ->assertRedirect()
                ->assertSessionHas('signature_status');

            $instructor->refresh();

            $this->assertStringStartsWith('certificates/instructor-signatures/', $instructor->signature_path);
            Storage::disk('public')->assertExists($instructor->signature_path);
        }
    }

    public function test_invalid_and_oversized_signature_files_are_rejected(): void
    {
        Storage::fake('public');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-validation@mkscholars.test');

        $this->actingAs($instructor)
            ->from(route('instructor.settings'))
            ->post(route('instructor.settings.signature'), [
                'signature' => UploadedFile::fake()->create('signature.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('instructor.settings'))
            ->assertSessionHasErrors('signature');

        $this->actingAs($instructor)
            ->from(route('instructor.settings'))
            ->post(route('instructor.settings.signature'), [
                'signature' => UploadedFile::fake()->create('signature.png', 2049, 'image/png'),
            ])
            ->assertRedirect(route('instructor.settings'))
            ->assertSessionHasErrors('signature');

        $this->assertNull($instructor->refresh()->signature_path);
    }

    public function test_instructor_can_replace_and_remove_own_signature(): void
    {
        Storage::fake('public');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-replace@mkscholars.test');
        Storage::disk('public')->put('certificates/instructor-signatures/old.png', 'old');
        $instructor->update(['signature_path' => 'certificates/instructor-signatures/old.png']);

        $this->actingAs($instructor)
            ->post(route('instructor.settings.signature'), [
                'signature' => UploadedFile::fake()->create('new.png', 128, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('signature_status');

        $newPath = $instructor->refresh()->signature_path;

        $this->assertNotSame('certificates/instructor-signatures/old.png', $newPath);
        Storage::disk('public')->assertMissing('certificates/instructor-signatures/old.png');
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($instructor)
            ->post(route('instructor.settings.signature.remove'))
            ->assertRedirect()
            ->assertSessionHas('signature_status');

        $this->assertNull($instructor->refresh()->signature_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_signature_update_is_limited_to_authenticated_instructors_own_record(): void
    {
        Storage::fake('public');
        $student = $this->user(User::ROLE_STUDENT, 'signature-student@mkscholars.test');
        $contentEditor = $this->user(User::ROLE_CONTENT_EDITOR, 'signature-content-editor@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-owner@mkscholars.test');
        $otherInstructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-other@mkscholars.test');

        $this->post(route('instructor.settings.signature'), [
            'signature' => UploadedFile::fake()->create('guest.png', 128, 'image/png'),
        ])->assertRedirect(route('login'));

        $this->actingAs($student)
            ->post(route('instructor.settings.signature'), [
                'signature' => UploadedFile::fake()->create('student.png', 128, 'image/png'),
            ])
            ->assertForbidden();

        $this->actingAs($contentEditor)
            ->post(route('instructor.settings.signature'), [
                'signature' => UploadedFile::fake()->create('content-editor.png', 128, 'image/png'),
            ])
            ->assertForbidden();

        $this->actingAs($instructor)
            ->post(route('instructor.settings.signature'), [
                'user_id' => $otherInstructor->id,
                'signature' => UploadedFile::fake()->create('owner.png', 128, 'image/png'),
            ])
            ->assertRedirect();

        $this->assertNotNull($instructor->refresh()->signature_path);
        $this->assertNull($otherInstructor->refresh()->signature_path);
    }

    public function test_issued_certificate_displays_instructor_uploaded_signature(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificates/instructor-signatures/display.png', 'signature');
        [$student, $certificate, $instructor] = $this->certificate();
        $instructor->update(['signature_path' => 'certificates/instructor-signatures/display.png']);

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Instructor signature')
            ->assertSee(Storage::disk('public')->url('certificates/instructor-signatures/display.png'), false);
    }

    public function test_certificate_still_renders_when_instructor_signature_is_missing(): void
    {
        [$student, $certificate] = $this->certificate();

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Course Instructor')
            ->assertDontSee('<img src=""', false);
    }

    public function test_admin_user_resource_signature_management_remains_available(): void
    {
        $resource = file_get_contents(app_path('Filament/Resources/Users/UserResource.php'));

        $this->assertStringContainsString("FileUpload::make('signature_path')", $resource);
        $this->assertStringContainsString("->directory('certificates/instructor-signatures')", $resource);
    }

    private function certificate(): array
    {
        $student = $this->user(User::ROLE_STUDENT, 'signature-certificate-student-'.str()->random(6).'@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'signature-certificate-instructor-'.str()->random(6).'@mkscholars.test');
        $academy = Academy::create([
            'name' => 'Signature Academy',
            'slug' => 'signature-academy-'.str()->random(6),
            'summary' => 'Academy',
            'description' => 'Academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Signature Course',
            'slug' => 'signature-course-'.str()->random(6),
            'short_description' => 'Course',
            'full_description' => 'Course',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'score' => 88,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        return [$student, $certificate, $instructor];
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
