<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificatePdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_owner_can_download_certificate(): void
    {
        [$student, $certificate] = $this->studentAndCertificate();

        $response = $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate));

        $response->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition', ''));
        $this->assertStringContainsString('mk-scholars', $response->headers->get('content-disposition', ''));

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $this->assertStringContainsString('application/pdf', $response->headers->get('content-type', ''));
            $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition', ''));
        } else {
            $this->assertStringContainsString('text/html', $response->headers->get('content-type', ''));
            $this->assertStringContainsString('.html', $response->headers->get('content-disposition', ''));
        }
    }

    public function test_guest_cannot_download_certificate(): void
    {
        [, $certificate] = $this->studentAndCertificate('guest-certificate@mkscholars.test', 'guest-certificate-course');

        $this->get(route('student.certificates.download', $certificate))
            ->assertRedirect(route('login'));
    }

    public function test_another_student_cannot_download_certificate(): void
    {
        [, $certificate] = $this->studentAndCertificate('owner-certificate@mkscholars.test', 'owner-certificate-course');
        $otherStudent = User::create([
            'name' => 'Other Student',
            'email' => 'other-certificate@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_admin_can_download_issued_certificate(): void
    {
        [, $certificate] = $this->studentAndCertificate('admin-download-certificate@mkscholars.test', 'admin-download-certificate-course');
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-download@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.certificates.download', $certificate))
            ->assertOk();
    }

    public function test_student_cannot_use_admin_certificate_download_route(): void
    {
        [$student, $certificate] = $this->studentAndCertificate('student-admin-route@mkscholars.test', 'student-admin-route-course');

        $this->actingAs($student)
            ->get(route('admin.certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_public_certificate_verification_still_works(): void
    {
        [, $certificate] = $this->studentAndCertificate('verify-certificate@mkscholars.test', 'verify-certificate-course');

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('Certificate Verified')
            ->assertSee($certificate->student_name)
            ->assertSee($certificate->course_title);
    }

    public function test_invalid_public_certificate_verification_shows_safe_state(): void
    {
        $this->get(route('certificates.verify', 'NOT-A-REAL-CODE'))
            ->assertOk()
            ->assertSee('Certificate Not Valid')
            ->assertDontSee('signature_image_path');
    }

    public function test_old_certificate_without_signature_fields_uses_safe_defaults(): void
    {
        [$student, $certificate] = $this->studentAndCertificate('old-certificate@mkscholars.test', 'old-certificate-course', [
            'signer_name' => null,
            'signer_title' => null,
            'signature_image_path' => null,
        ]);

        $this->assertFalse($certificate->signatureImageExists());
        $this->assertNull($certificate->signatureImageUrl());
        $this->assertNull($certificate->signatureImageDataUri());

        $response = $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate));

        $response->assertOk();

        if (! class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $response->assertSee('Authorized Signature')
                ->assertDontSee('signature_image_path')
                ->assertDontSee('<img src=""', false);
        }
    }

    public function test_missing_optional_fields_do_not_break_certificate_download(): void
    {
        [$student, $certificate] = $this->studentAndCertificate('optional-certificate@mkscholars.test', 'optional-certificate-course', [
            'score' => null,
        ]);

        $certificate->skills()->delete();

        $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate))
            ->assertOk();
    }

    public function test_certificate_download_displays_signer_without_uploaded_image(): void
    {
        [$student, $certificate] = $this->studentAndCertificate('signer-certificate@mkscholars.test', 'signer-certificate-course', [
            'signer_name' => 'Aline Director',
            'signer_title' => 'Director of Learning',
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate));

        $response->assertOk();

        if (! class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $response->assertSee('Aline Director')
                ->assertSee('Director of Learning')
                ->assertDontSee('<img src=""', false);
        }
    }

    public function test_missing_signature_image_file_does_not_render_broken_image_or_path(): void
    {
        Storage::fake('public');
        [$student, $certificate] = $this->studentAndCertificate('missing-signature@mkscholars.test', 'missing-signature-course', [
            'signer_name' => 'Missing File Signer',
            'signature_image_path' => 'certificates/signatures/missing-signature.png',
        ]);

        $this->assertFalse($certificate->signatureImageExists());
        $this->assertNull($certificate->signatureImageUrl());
        $this->assertNull($certificate->signatureImageDataUri());

        $response = $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate));

        $response->assertOk();

        if (! class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $response->assertSee('Missing File Signer')
                ->assertDontSee('certificates/signatures/missing-signature.png')
                ->assertDontSee('<img src=""', false);
        }
    }

    public function test_existing_signature_image_is_embedded_without_exposing_storage_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificates/signatures/signature.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lK3QxwAAAABJRU5ErkJggg=='
        ));

        [$student, $certificate] = $this->studentAndCertificate('signature-image@mkscholars.test', 'signature-image-course', [
            'signer_name' => 'Signed Image User',
            'signature_image_path' => 'certificates/signatures/signature.png',
        ]);

        $this->assertTrue($certificate->signatureImageExists());
        $this->assertStringStartsWith('data:image/', $certificate->signatureImageDataUri());

        $response = $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate));

        $response->assertOk();

        if (! class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $response->assertSee('data:image/', false)
                ->assertDontSee('certificates/signatures/signature.png');
        }
    }

    public function test_public_certificate_verification_can_show_signer_details(): void
    {
        [, $certificate] = $this->studentAndCertificate('verify-signer@mkscholars.test', 'verify-signer-course', [
            'signer_name' => 'Verification Signer',
            'signer_title' => 'Academic Director',
        ]);

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('Verification Signer')
            ->assertSee('Academic Director')
            ->assertDontSee('signature_image_path');
    }

    public function test_public_certificate_verification_hides_missing_signature_image_path(): void
    {
        Storage::fake('public');
        [, $certificate] = $this->studentAndCertificate('verify-missing-signature@mkscholars.test', 'verify-missing-signature-course', [
            'signer_name' => 'Public Missing Signer',
            'signature_image_path' => 'certificates/signatures/public-missing.png',
        ]);

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('Public Missing Signer')
            ->assertDontSee('certificates/signatures/public-missing.png')
            ->assertDontSee('<img src=""', false);
    }

    public function test_long_certificate_names_do_not_crash_certificate_rendering(): void
    {
        [$student, $certificate] = $this->studentAndCertificate('long-certificate@mkscholars.test', 'long-certificate-course', [
            'student_name' => str_repeat('Long Student Name ', 8),
            'course_title' => str_repeat('Advanced MK Scholars Certificate Course ', 5),
            'signer_name' => str_repeat('Long Signer Name ', 4),
            'signer_title' => str_repeat('Senior Academic Director ', 3),
        ]);

        $this->actingAs($student)
            ->get(route('student.certificates.download', $certificate))
            ->assertOk();

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('Certificate Verified');
    }

    private function studentAndCertificate(string $email = 'certificate-owner@mkscholars.test', string $slug = 'certificate-course', array $overrides = []): array
    {
        $student = User::create([
            'name' => 'Certificate Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $academy = Academy::create([
            'name' => 'Certificate Academy '.$slug,
            'slug' => 'certificate-academy-'.$slug,
            'summary' => 'Demo academy',
            'description' => 'Demo academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Certificate Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Certificate course',
            'full_description' => 'Certificate course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'currency' => 'RWF',
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $certificate = Certificate::create(array_merge([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'score' => 92,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ], $overrides));

        $certificate->skills()->create(['skill_name' => 'Focused study']);

        return [$student, $certificate];
    }
}
