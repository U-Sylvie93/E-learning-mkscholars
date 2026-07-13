<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CertificateApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_preparation_creates_one_pending_certificate_and_preserves_score_and_code(): void
    {
        [$student, $course] = $this->scenario();
        $service = app(CertificateService::class);

        $first = $service->prepareForEligibleCompletion($student, $course);
        $second = $service->prepareForEligibleCompletion($student, $course);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(Certificate::STATUS_PENDING, $first->status);
        $this->assertNotEmpty($first->verification_code);
        $this->assertSame(1, Certificate::query()->where('user_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_admin_can_issue_reject_and_revoke_with_safe_transitions(): void
    {
        [$student, $course] = $this->scenario();
        $admin = $this->user(User::ROLE_ADMIN);
        $service = app(CertificateService::class);
        $certificate = $service->prepareForEligibleCompletion($student, $course);
        $verificationCode = $certificate->verification_code;

        $service->reject($certificate, $admin, 'Completion evidence requires review.');
        $this->assertSame(Certificate::STATUS_REJECTED, $certificate->refresh()->status);
        $this->assertSame('Completion evidence requires review.', $certificate->rejection_reason);

        $service->issue($certificate, $admin);
        $this->assertSame(Certificate::STATUS_ISSUED, $certificate->refresh()->status);
        $this->assertSame($verificationCode, $certificate->verification_code);
        $this->assertSame($admin->id, $certificate->reviewed_by);

        $service->revoke($certificate, $admin);
        $this->assertSame(Certificate::STATUS_REVOKED, $certificate->refresh()->status);
        $this->assertNotNull($certificate->revoked_at);
    }

    public function test_non_admin_roles_cannot_run_certificate_workflow_actions(): void
    {
        [$student, $course] = $this->scenario();
        $certificate = app(CertificateService::class)->prepareForEligibleCompletion($student, $course);

        foreach ([User::ROLE_VIEWER, User::ROLE_CONTENT_EDITOR, User::ROLE_INSTRUCTOR, User::ROLE_STUDENT] as $role) {
            try {
                app(CertificateService::class)->issue($certificate, $this->user($role));
                $this->fail($role.' unexpectedly issued a certificate.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        $this->assertSame(Certificate::STATUS_PENDING, $certificate->refresh()->status);
    }

    public function test_public_verification_only_validates_issued_certificates(): void
    {
        [$student, $course] = $this->scenario();
        $admin = $this->user(User::ROLE_ADMIN);
        $service = app(CertificateService::class);
        $certificate = $service->prepareForEligibleCompletion($student, $course);

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()->assertSee('Certificate Not Yet Issued')->assertDontSee('Certificate Verified');

        $service->issue($certificate, $admin);
        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()->assertSee('Certificate Verified');

        $service->revoke($certificate, $admin);
        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()->assertSee('Certificate Not Valid')->assertSee('revoked')->assertDontSee('Certificate Verified');
    }

    public function test_pending_student_view_hides_official_assets_and_qr(): void
    {
        [$student, $course] = $this->scenario();
        $certificate = app(CertificateService::class)->prepareForEligibleCompletion($student, $course);

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Pending Approval')
            ->assertDontSee('Official Stamp')
            ->assertDontSee('Scan to verify this certificate')
            ->assertDontSee('Print');
    }

    private function scenario(): array
    {
        $student = $this->user(User::ROLE_STUDENT);
        $instructor = $this->user(User::ROLE_INSTRUCTOR);
        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);

        return [$student, $course];
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => str($role)->headline().' Workflow User',
            'email' => $role.'-'.str()->random(10).'@example.test',
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }
}
