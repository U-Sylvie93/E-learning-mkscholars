<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTierSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_requires_the_student_to_choose_a_payment_tier(): void
    {
        [$student, $course] = $this->tieredCourse();

        $this->actingAs($student)
            ->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('href="#course-pricing"', false)
            ->assertSee('Choose Payment Tier')
            ->assertSee('Pay Basic')
            ->assertSee('Pay Premium');
    }

    public function test_enrollment_does_not_choose_a_tier_for_the_student(): void
    {
        [$student, $course] = $this->tieredCourse();

        $this->actingAs($student)
            ->from(route('courses.show', $course->slug))
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('courses.show', $course->slug))
            ->assertSessionHasErrors('tier');

        $this->assertDatabaseMissing('payments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_selected_tier_sets_the_correct_payment_tier_and_amount(): void
    {
        [$student, $course] = $this->tieredCourse();

        $response = $this->actingAs($student)
            ->post(route('courses.enroll', $course), ['tier' => Course::TIER_PREMIUM]);

        $payment = Payment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $response->assertRedirect(route('student.payments.show', $payment));
        $this->assertSame(Course::TIER_PREMIUM, $payment->tier);
        $this->assertSame('75000.00', $payment->amount);
    }

    private function tieredCourse(): array
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $academy = Academy::factory()->create([
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => false,
            'access_type' => Course::ACCESS_PAID,
            'price_amount' => 25000,
            'price_basic' => 25000,
            'price_premium' => 75000,
            'currency' => 'RWF',
        ]);

        return [$student, $course];
    }
}
