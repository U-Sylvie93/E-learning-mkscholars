<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMyCoursesPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_courses_separates_active_and_pending_payment_courses(): void
    {
        $student = $this->student('student-my-courses@mkscholars.test');
        $activeCourse = $this->course('active-course', false);
        $pendingCourse = $this->course('pending-course', false);

        $approvedPayment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $activeCourse->id,
            'amount' => $activeCourse->payableAmount(),
            'currency' => $activeCourse->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_SUBMITTED,
        ]);
        $approvedPayment->update(['status' => Payment::STATUS_APPROVED]);

        Payment::create([
            'user_id' => $student->id,
            'course_id' => $pendingCourse->id,
            'amount' => $pendingCourse->payableAmount(),
            'currency' => $pendingCourse->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_SUBMITTED,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee('Paid / Active Courses')
            ->assertSee($activeCourse->title)
            ->assertSee('Unpaid Courses / Courses Awaiting Payment')
            ->assertSee($pendingCourse->title)
            ->assertSee('Payment Pending');
    }

    public function test_pending_payment_pay_now_reuses_existing_payment(): void
    {
        $student = $this->student('pending-payment@mkscholars.test');
        $course = $this->course('pending-reuse-course', false);
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($student)
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('student.payments.show', $payment));

        $this->assertSame(1, Payment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('purpose', Payment::PURPOSE_COURSE)
            ->count());
    }

    public function test_rejected_payment_course_allows_retry_from_my_courses(): void
    {
        $student = $this->student('rejected-payment@mkscholars.test');
        $course = $this->course('rejected-course', false);
        $payment = Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_REJECTED,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee('Payment Rejected')
            ->assertSee('Pay Again')
            ->assertSee(route('student.payments.show', $payment), false);
    }

    public function test_student_cannot_see_another_students_unpaid_course(): void
    {
        $student = $this->student('owner@mkscholars.test');
        $otherStudent = $this->student('other@mkscholars.test');
        $course = $this->course('private-pending-course', false);

        Payment::create([
            'user_id' => $otherStudent->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertDontSee($course->title);
    }

    public function test_guest_cannot_access_my_courses(): void
    {
        $this->get(route('student.my-courses'))->assertRedirect(route('login'));
    }

    public function test_completed_course_and_certificate_status_appear_for_active_course(): void
    {
        $student = $this->student('completed-course@mkscholars.test');
        $course = $this->course('completed-course', true);
        $lesson = $course->modules()->first()->lessons()->first();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        CourseCompletion::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'lesson_percentage' => 100,
            'quiz_percentage' => 100,
            'assignment_percentage' => 100,
            'is_eligible_for_certificate' => true,
            'completed_at' => now(),
            'last_checked_at' => now(),
        ]);
        Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'status' => Certificate::STATUS_ISSUED,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Certificate issued');
    }

    public function test_incomplete_active_course_shows_continue_learning(): void
    {
        $student = $this->student('incomplete-course@mkscholars.test');
        $course = $this->course('incomplete-course', true);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee('Continue Learning');
    }

    public function test_my_courses_does_not_render_hardcoded_http_payment_links(): void
    {
        $student = $this->student('secure-links@mkscholars.test');
        $course = $this->course('secure-payment-course', false);

        Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->payableAmount(),
            'currency' => $course->currency,
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertDontSee('http://mkscholars', false)
            ->assertDontSee('https://mkscholars', false);
    }

    public function test_expired_subscription_course_can_renew_from_my_courses(): void
    {
        $student = $this->student('expired-subscription@mkscholars.test');
        $course = $this->course('expired-subscription-course', false);
        $plan = SubscriptionPlan::create([
            'name' => 'Expired Plan',
            'slug' => 'expired-plan',
            'price_amount' => 50000,
            'currency' => 'RWF',
            'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
            'duration_days' => 30,
            'status' => SubscriptionPlan::STATUS_ACTIVE,
        ]);
        $plan->courses()->attach($course);
        $subscription = Subscription::create([
            'user_id' => $student->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee('Expired')
            ->assertSee('Renew Plan')
            ->assertSee(route('student.subscriptions.renew', $subscription), false);
    }

    private function student(string $email): User
    {
        return User::create([
            'name' => 'Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }

    private function course(string $slug, bool $isFree): Course
    {
        $academy = Academy::create([
            'name' => 'Academy '.$slug,
            'slug' => 'academy-'.$slug,
            'summary' => 'Demo academy',
            'description' => 'Demo academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $instructor = User::create([
            'name' => 'Instructor '.$slug,
            'email' => 'instructor-'.$slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
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
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson '.$slug,
            'slug' => 'lesson-'.$slug,
            'lesson_type' => 'text',
            'content' => 'Lesson content',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return $course;
    }
}
