<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\PaymentProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentProviderArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_provider_defaults_to_manual(): void
    {
        [$student] = $this->studentAndPaidCourse();

        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => 10000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->assertSame(Payment::PROVIDER_MANUAL, $payment->provider);
        $this->assertTrue($payment->isManualProvider());
        $this->assertFalse($payment->isExternalProvider());
        $this->assertSame('Manual', $payment->providerLabel());
    }

    public function test_old_payment_with_null_provider_displays_safely(): void
    {
        [$student] = $this->studentAndPaidCourse('old-provider@mkscholars.test', 'old-provider-course');

        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => 10000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_APPROVED,
        ]);

        DB::table('payments')->where('id', $payment->id)->update(['provider' => null]);

        $payment->refresh();

        $this->assertNull($payment->provider);
        $this->assertTrue($payment->isManualProvider());
        $this->assertFalse($payment->isExternalProvider());
        $this->assertSame('Manual', $payment->providerLabel());
    }

    public function test_provider_fields_are_nullable(): void
    {
        [$student] = $this->studentAndPaidCourse('nullable-provider@mkscholars.test', 'nullable-provider-course');

        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => 15000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
            'provider' => Payment::PROVIDER_MANUAL,
            'provider_reference' => null,
            'provider_status' => null,
            'provider_payload' => null,
            'provider_callback_received_at' => null,
        ]);

        $this->assertFalse($payment->hasProviderReference());
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'provider' => Payment::PROVIDER_MANUAL,
            'provider_reference' => null,
            'provider_status' => null,
        ]);
    }

    public function test_unknown_provider_fails_safely(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PaymentProviderManager::class)->driver('unknown_gateway');
    }

    public function test_new_manual_course_payment_has_manual_provider(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('route-course-payment@mkscholars.test', 'route-course-payment');

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertRedirect();

        $payment = Payment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('purpose', Payment::PURPOSE_COURSE)
            ->firstOrFail();

        $this->assertSame(Payment::PROVIDER_MANUAL, $payment->provider);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
    }

    public function test_new_manual_subscription_payment_has_manual_provider(): void
    {
        [$student, , $plan] = $this->studentCourseAndPlan('route-subscription-payment@mkscholars.test', 'route-subscription-payment');

        $this->actingAs($student)
            ->post(route('subscriptions.choose', $plan))
            ->assertRedirect();

        $subscription = Subscription::query()
            ->where('user_id', $student->id)
            ->where('subscription_plan_id', $plan->id)
            ->firstOrFail();

        $payment = $subscription->payment()->firstOrFail();

        $this->assertSame(Payment::PROVIDER_MANUAL, $payment->provider);
        $this->assertSame(Payment::PURPOSE_SUBSCRIPTION, $payment->purpose);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
    }

    public function test_webhook_placeholder_does_not_approve_payment(): void
    {
        [$student] = $this->studentAndPaidCourse('webhook-provider@mkscholars.test', 'webhook-provider-course');
        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => 20000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_reference' => 'future-ref-123',
        ]);

        $this->postJson(route('payments.webhooks', Payment::PROVIDER_STRIPE), [
            'reference' => 'future-ref-123',
            'status' => 'paid',
        ])->assertStatus(501);

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertNull($payment->provider_callback_received_at);
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $student->id,
        ]);
    }

    public function test_manual_course_payment_approval_still_grants_access(): void
    {
        [$student, $course] = $this->studentAndPaidCourse('manual-access@mkscholars.test', 'manual-access-course');

        $payment = app(PaymentProviderManager::class)
            ->driver(Payment::PROVIDER_MANUAL)
            ->createPendingPayment([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->payableAmount(),
                'currency' => $course->currency,
                'purpose' => Payment::PURPOSE_COURSE,
            ]);

        $payment->update(['status' => Payment::STATUS_APPROVED]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk();
    }

    public function test_manual_subscription_payment_approval_still_grants_access(): void
    {
        [$student, $course, $plan] = $this->studentCourseAndPlan('manual-subscription-access@mkscholars.test', 'manual-subscription-access');
        $payment = app(PaymentProviderManager::class)
            ->driver(Payment::PROVIDER_MANUAL)
            ->createPendingPayment([
                'user_id' => $student->id,
                'amount' => $plan->price_amount,
                'currency' => $plan->currency,
                'purpose' => Payment::PURPOSE_SUBSCRIPTION,
            ]);

        $subscription = Subscription::create([
            'user_id' => $student->id,
            'subscription_plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $payment->update(['status' => Payment::STATUS_APPROVED]);

        $subscription->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->isFuture());

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk();
    }

    public function test_free_course_still_works_without_payment_provider_metadata(): void
    {
        [$student, $course] = $this->studentAndCourse('free-provider-flow@mkscholars.test', 'free-provider-flow', true);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('student.my-courses'));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseMissing('payments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    private function studentAndPaidCourse(string $email = 'payment-provider@mkscholars.test', string $slug = 'payment-provider-course'): array
    {
        return $this->studentAndCourse($email, $slug, false);
    }

    private function studentCourseAndPlan(string $email, string $slug): array
    {
        [$student, $course] = $this->studentAndPaidCourse($email, $slug);

        $plan = SubscriptionPlan::create([
            'name' => 'Provider Plan '.$slug,
            'slug' => 'provider-plan-'.$slug,
            'price_amount' => 50000,
            'currency' => 'RWF',
            'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
            'duration_days' => 30,
            'status' => SubscriptionPlan::STATUS_ACTIVE,
        ]);

        $plan->courses()->attach($course);

        return [$student, $course, $plan];
    }

    private function studentAndCourse(string $email, string $slug, bool $isFree): array
    {
        $student = User::create([
            'name' => 'Payment Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $academy = Academy::create([
            'name' => 'Payment Academy '.$slug,
            'slug' => 'payment-academy-'.$slug,
            'summary' => 'Demo academy',
            'description' => 'Demo academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Payment Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Demo paid course',
            'full_description' => 'Demo paid course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'price' => $isFree ? null : 30000,
            'is_free' => $isFree,
            'price_amount' => $isFree ? null : 30000,
            'currency' => 'RWF',
            'access_type' => $isFree ? Course::ACCESS_FREE : Course::ACCESS_PAID,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Payment Module',
            'slug' => 'payment-module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Payment Lesson',
            'slug' => 'payment-lesson-'.$slug,
            'lesson_type' => 'text',
            'content' => 'Lesson content',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$student, $course];
    }
}
