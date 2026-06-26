<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_submit_course_review(): void
    {
        [$student, $course] = $this->enrolledCourse();

        $this->actingAs($student)
            ->post(route('student.course-reviews.store', $course), [
                'rating' => 5,
                'comment' => 'A clear and useful course.',
            ])
            ->assertRedirect(route('student.courses.learn', $course));

        $this->assertDatabaseHas('course_reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'status' => CourseReview::STATUS_PENDING,
        ]);
    }

    public function test_guest_cannot_submit_course_review(): void
    {
        [, $course] = $this->course();

        $this->post(route('student.course-reviews.store', $course), [
            'rating' => 5,
            'comment' => 'Guest review.',
        ])->assertRedirect(route('login'));
    }

    public function test_non_enrolled_student_cannot_submit_course_review(): void
    {
        [$student, $course] = $this->course();

        $this->actingAs($student)
            ->post(route('student.course-reviews.store', $course), [
                'rating' => 4,
                'comment' => 'Trying without enrollment.',
            ])
            ->assertForbidden();
    }

    public function test_review_rating_must_be_between_one_and_five(): void
    {
        [$student, $course] = $this->enrolledCourse();

        $this->actingAs($student)
            ->post(route('student.course-reviews.store', $course), [
                'rating' => 6,
                'comment' => 'Invalid rating.',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('course_reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_review_comment_can_be_empty(): void
    {
        [$student, $course] = $this->enrolledCourse();

        $this->actingAs($student)
            ->post(route('student.course-reviews.store', $course), [
                'rating' => 4,
            ])
            ->assertRedirect(route('student.courses.learn', $course));

        $this->assertDatabaseHas('course_reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    public function test_duplicate_course_review_is_blocked(): void
    {
        [$student, $course] = $this->enrolledCourse();

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 4,
            'status' => CourseReview::STATUS_PENDING,
        ]);

        $this->actingAs($student)
            ->post(route('student.course-reviews.store', $course), [
                'rating' => 5,
                'comment' => 'Second review.',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertSame(1, CourseReview::query()->where('user_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_pending_review_is_not_public(): void
    {
        [$student, $course] = $this->enrolledCourse();

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => 'Pending review should stay private.',
            'status' => CourseReview::STATUS_PENDING,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('Pending review should stay private.');
    }

    public function test_hidden_review_is_not_public(): void
    {
        [$student, $course] = $this->enrolledCourse();

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => 'Hidden review should stay private.',
            'status' => CourseReview::STATUS_HIDDEN,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('Hidden review should stay private.');
    }

    public function test_published_review_is_public(): void
    {
        [$student, $course] = $this->enrolledCourse();

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => 'Published review should be visible.',
            'status' => CourseReview::STATUS_PUBLISHED,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('Published review should be visible.')
            ->assertSee('5/5');
    }

    public function test_average_rating_and_count_only_use_published_reviews(): void
    {
        [$student, $course] = $this->enrolledCourse();
        $secondStudent = $this->student('second-reviewer@mkscholars.test');
        $thirdStudent = $this->student('third-reviewer@mkscholars.test');
        $fourthStudent = $this->student('fourth-reviewer@mkscholars.test');

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => 'Published five.',
            'status' => CourseReview::STATUS_PUBLISHED,
        ]);
        CourseReview::create([
            'user_id' => $secondStudent->id,
            'course_id' => $course->id,
            'rating' => 3,
            'comment' => 'Published three.',
            'status' => CourseReview::STATUS_PUBLISHED,
        ]);
        CourseReview::create([
            'user_id' => $thirdStudent->id,
            'course_id' => $course->id,
            'rating' => 1,
            'comment' => 'Pending one.',
            'status' => CourseReview::STATUS_PENDING,
        ]);
        CourseReview::create([
            'user_id' => $fourthStudent->id,
            'course_id' => $course->id,
            'rating' => 1,
            'comment' => 'Hidden one.',
            'status' => CourseReview::STATUS_HIDDEN,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('4')
            ->assertSee('2 published reviews')
            ->assertDontSee('Pending one.')
            ->assertDontSee('Hidden one.');
    }

    private function enrolledCourse(): array
    {
        [$student, $course] = $this->course();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $course];
    }

    private function course(): array
    {
        $student = $this->student();
        $academy = Academy::create([
            'name' => 'Review Academy '.str()->random(6),
            'slug' => 'review-academy-'.str()->random(6),
            'summary' => 'Review academy',
            'description' => 'Review academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Review Course '.str()->random(6),
            'slug' => 'review-course-'.str()->random(8),
            'short_description' => 'Review course',
            'full_description' => 'Review course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$student, $course];
    }

    private function student(?string $email = null): User
    {
        return User::create([
            'name' => 'Student',
            'email' => $email ?? 'student-'.str()->random(8).'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
    }
}
