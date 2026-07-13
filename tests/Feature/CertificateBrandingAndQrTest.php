<?php

namespace Tests\Feature;

use App\Filament\Resources\CertificateSettings\CertificateSettingResource;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificateQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateBrandingAndQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_manage_official_certificate_settings(): void
    {
        foreach ([User::ROLE_STUDENT, User::ROLE_INSTRUCTOR, User::ROLE_VIEWER, User::ROLE_CONTENT_EDITOR] as $role) {
            $this->actingAs($this->user($role));
            $this->assertFalse(CertificateSettingResource::canViewAny());
            $this->assertFalse(CertificateSettingResource::canCreate());
        }

        $this->actingAs($this->user(User::ROLE_ADMIN));
        $this->assertTrue(CertificateSettingResource::canViewAny());
        $this->assertTrue(CertificateSettingResource::canCreate());
    }

    public function test_qr_uses_public_verification_code_and_not_database_id(): void
    {
        [, $certificate] = $this->certificate();
        $service = app(CertificateQrCodeService::class);

        $this->assertSame(route('certificates.verify', $certificate->verification_code), $service->verificationUrl($certificate));
        $this->assertStringNotContainsString('/'.$certificate->id, $service->verificationUrl($certificate));
        $this->assertStringStartsWith('data:image/', $service->dataUri($certificate));
    }

    public function test_certificate_page_shows_optional_official_and_instructor_assets(): void
    {
        Storage::fake('public');
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lK3QxwAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('certificates/stamp.png', $image);
        Storage::disk('public')->put('certificates/issuer.png', $image);
        Storage::disk('public')->put('certificates/instructor.png', $image);

        CertificateSetting::create([
            'organization_name' => 'MK Scholars',
            'stamp_path' => 'certificates/stamp.png',
            'admin_signature_path' => 'certificates/issuer.png',
            'issuer_name' => 'Academic Director',
            'issuer_title' => 'Issuer',
        ]);

        [$student, $certificate, $instructor] = $this->certificate();
        $instructor->update(['signature_path' => 'certificates/instructor.png']);

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Scan to verify this certificate')
            ->assertSee('Official Stamp')
            ->assertSee($instructor->name)
            ->assertSee('Academic Director');
    }

    public function test_missing_assets_do_not_break_certificate_page(): void
    {
        [$student, $certificate] = $this->certificate();

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Scan to verify this certificate')
            ->assertDontSee('<img src=""', false);
    }

    private function certificate(): array
    {
        $student = $this->user(User::ROLE_STUDENT);
        $instructor = $this->user(User::ROLE_INSTRUCTOR);
        $academy = Academy::create([
            'name' => 'Certificate Branding Academy',
            'slug' => 'certificate-branding-academy-'.str()->random(6),
            'summary' => 'Academy',
            'description' => 'Academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Certificate Branding Course',
            'slug' => 'certificate-branding-course-'.str()->random(6),
            'short_description' => 'Course',
            'full_description' => 'Course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'score' => 91,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        return [$student, $certificate, $instructor];
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => str($role)->headline().' User',
            'email' => $role.'-'.str()->random(8).'@mkscholars.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
