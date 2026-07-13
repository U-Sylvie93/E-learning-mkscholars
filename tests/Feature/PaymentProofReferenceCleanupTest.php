<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofReferenceCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_payment_proof_form_does_not_show_reference_number_input(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('reference-form-cleanup');
        $payment = $this->payment($student, $course);
        $this->paymentMethod();

        $this->actingAs($student)
            ->get(route('student.payments.show', $payment))
            ->assertOk()
            ->assertSee('Submit payment proof')
            ->assertSee('Payment method')
            ->assertSee('Proof file')
            ->assertDontSee('Reference number')
            ->assertDontSee('name="reference"', false);
    }

    public function test_student_can_submit_payment_proof_without_reference_number(): void
    {
        Storage::fake('public');

        [$student, $course] = $this->studentAndPaidCourse('reference-submit-cleanup');
        $payment = $this->payment($student, $course);
        $method = $this->paymentMethod();

        $this->actingAs($student)
            ->post(route('student.payments.submit', $payment), [
                'payment_method_id' => $method->id,
                'proof_file' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertRedirect(route('student.payments.show', $payment));

        $payment->refresh();

        $this->assertSame(Payment::STATUS_SUBMITTED, $payment->status);
        $this->assertNull($payment->reference);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_reference_validation_is_not_required_and_legacy_value_is_preserved(): void
    {
        Storage::fake('public');

        [$student, $course] = $this->studentAndPaidCourse('reference-legacy-cleanup');
        $payment = $this->payment($student, $course, [
            'reference' => 'OLD-REFERENCE-001',
            'status' => Payment::STATUS_REJECTED,
        ]);
        $method = $this->paymentMethod();

        $this->actingAs($student)
            ->post(route('student.payments.submit', $payment), [
                'payment_method_id' => $method->id,
                'proof_file' => UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('student.payments.show', $payment));

        $this->assertSame('OLD-REFERENCE-001', $payment->refresh()->reference);
    }

    public function test_old_reference_displays_as_legacy_information_only(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('reference-display-cleanup');
        $payment = $this->payment($student, $course, [
            'reference' => 'OLD-REFERENCE-002',
        ]);
        $this->paymentMethod();

        $this->actingAs($student)
            ->get(route('student.payments.show', $payment))
            ->assertOk()
            ->assertSee('Legacy Reference')
            ->assertSee('OLD-REFERENCE-002')
            ->assertDontSee('Reference number');
    }

    public function test_admin_can_approve_payment_without_reference_number(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('reference-admin-cleanup');
        $payment = $this->payment($student, $course, [
            'reference' => null,
            'status' => Payment::STATUS_SUBMITTED,
        ]);

        $payment->update([
            'status' => Payment::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $this->assertNull($payment->refresh()->reference);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_resource_treats_reference_as_legacy_optional_display(): void
    {
        $resource = file_get_contents(app_path('Filament/Resources/Payments/PaymentResource.php'));

        $this->assertStringContainsString("->label('Legacy Reference')", $resource);
        $this->assertStringContainsString("->visible(fn (?Payment \$record): bool => filled(\$record?->reference))", $resource);
        $this->assertStringNotContainsString("TextInput::make('reference')->required()", $resource);
    }

    public function test_my_courses_pay_now_and_pending_reuse_still_route_to_payment(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('reference-my-courses-cleanup');
        $payment = $this->payment($student, $course);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee('Pay Now')
            ->assertSee(route('student.payments.show', $payment), false);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('student.payments.show', $payment));

        $this->assertSame(1, Payment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('purpose', Payment::PURPOSE_COURSE)
            ->count());
    }

    public function test_rejected_payment_retry_still_routes_without_insecure_form_action(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('reference-retry-cleanup');
        $payment = $this->payment($student, $course, [
            'status' => Payment::STATUS_REJECTED,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee('Pay Again')
            ->assertSee(route('student.payments.show', $payment), false)
            ->assertDontSee('http://mkscholars', false)
            ->assertDontSee('https://mkscholars', false);
    }

    private function studentAndPaidCourse(string $slug): array
    {
        $student = User::create([
            'name' => 'Reference Cleanup Student',
            'email' => $slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $academy = Academy::create([
            'name' => 'Reference Cleanup Academy '.$slug,
            'slug' => 'reference-cleanup-academy-'.$slug,
            'summary' => 'Demo academy',
            'description' => 'Demo academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Reference Cleanup Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Demo paid course',
            'full_description' => 'Demo paid course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'price_amount' => 50000,
            'currency' => 'RWF',
            'access_type' => Course::ACCESS_PAID,
            'is_free' => false,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Reference Cleanup Module',
            'slug' => 'reference-cleanup-module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Reference Cleanup Lesson',
            'slug' => 'reference-cleanup-lesson-'.$slug,
            'lesson_type' => 'text',
            'content' => 'Lesson content',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$student, $course];
    }

    private function payment(User $student, Course $course, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
            'reference' => null,
        ], $overrides));
    }

    private function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => 'Mobile Money',
            'type' => PaymentMethod::TYPE_MOMO,
            'account_name' => 'MK Scholars',
            'account_number' => '250000000000',
            'instructions' => 'Upload proof after payment.',
            'status' => PaymentMethod::STATUS_ACTIVE,
        ]);
    }
}
