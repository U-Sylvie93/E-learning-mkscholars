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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_grants_access_to_included_paid_course(): void
    {
        [$student, $course, $plan] = $this->courseWithPlan();

        Subscription::create([
            'user_id' => $student->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk();
    }

    public function test_non_active_subscriptions_do_not_grant_paid_course_access(): void
    {
        foreach ([Subscription::STATUS_PENDING, Subscription::STATUS_REJECTED, Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED] as $status) {
            [$student, $course, $plan] = $this->courseWithPlan('student-'.$status.'@mkscholars.test', 'course-'.$status);

            Subscription::create([
                'user_id' => $student->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => now()->subDays(10),
                'ends_at' => $status === Subscription::STATUS_EXPIRED ? now()->subDay() : now()->addDays(10),
            ]);

            $this->actingAs($student)
                ->get(route('student.courses.learn', $course))
                ->assertForbidden();
        }
    }

    public function test_approved_subscription_renewal_extends_once(): void
    {
        [$student, , $plan] = $this->courseWithPlan();
        $currentEnd = now()->addDays(10)->startOfSecond();
        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => $plan->price_amount,
            'currency' => $plan->currency,
            'purpose' => Payment::PURPOSE_SUBSCRIPTION,
            'status' => Payment::STATUS_SUBMITTED,
        ]);
        $subscription = Subscription::create([
            'user_id' => $student->id,
            'subscription_plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(20),
            'ends_at' => $currentEnd,
        ]);

        $payment->update([
            'status' => Payment::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $subscription->refresh();
        $expectedEnd = $currentEnd->copy()->addDays($plan->durationDays());
        $this->assertTrue($subscription->ends_at->equalTo($expectedEnd));

        $payment->update(['status' => Payment::STATUS_REJECTED]);
        $payment->update([
            'status' => Payment::STATUS_APPROVED,
            'reviewed_at' => now()->addMinute(),
        ]);

        $this->assertTrue($subscription->refresh()->ends_at->equalTo($expectedEnd));
    }

    public function test_course_specific_approved_payment_still_grants_access(): void
    {
        [$student, $course] = $this->paidCourse();
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_SUBMITTED,
        ]);

        $payment->update(['status' => Payment::STATUS_APPROVED]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk();
    }

    public function test_free_course_still_enrolls_without_payment(): void
    {
        [$student, $course] = $this->freeCourse();

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('student.my-courses'));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]);
    }

    private function courseWithPlan(string $email = 'student@mkscholars.test', string $courseSlug = 'paid-course'): array
    {
        [$student, $course] = $this->paidCourse($email, $courseSlug);
        $plan = SubscriptionPlan::create([
            'name' => 'Scholar Plan '.$courseSlug,
            'slug' => 'scholar-plan-'.$courseSlug,
            'price_amount' => 50000,
            'currency' => 'RWF',
            'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
            'duration_days' => 30,
            'status' => SubscriptionPlan::STATUS_ACTIVE,
        ]);
        $plan->courses()->attach($course);

        return [$student, $course, $plan];
    }

    private function paidCourse(string $email = 'student@mkscholars.test', string $slug = 'paid-course'): array
    {
        return $this->course($email, $slug, false);
    }

    private function freeCourse(): array
    {
        return $this->course('free-student@mkscholars.test', 'free-course', true);
    }

    private function course(string $email, string $slug, bool $isFree): array
    {
        $student = User::create([
            'name' => 'Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        $academy = Academy::create([
            'name' => 'Demo Academy '.$slug,
            'slug' => 'demo-academy-'.$slug,
            'summary' => 'Demo',
            'description' => 'Demo',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Demo Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Demo course',
            'full_description' => 'Demo course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'price' => $isFree ? null : 50000,
            'is_free' => $isFree,
            'price_amount' => $isFree ? null : 50000,
            'currency' => 'RWF',
            'access_type' => $isFree ? Course::ACCESS_FREE : Course::ACCESS_PAID,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module',
            'slug' => 'module',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson',
            'slug' => 'lesson',
            'lesson_type' => 'text',
            'content' => 'Lesson content',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$student, $course];
    }
}
