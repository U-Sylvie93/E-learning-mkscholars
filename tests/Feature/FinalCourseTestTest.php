<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalCourseTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_final_test_for_owned_course(): void
    {
        [$instructor, $course] = $this->courseScenario();

        $this->actingAs($instructor)
            ->post(route('instructor.final-tests.store', $course), [
                'title' => 'Final Test',
                'description' => 'Complete this final test at the end of the course.',
                'passing_score' => 75,
                'time_limit_minutes' => 30,
                'max_attempts' => 2,
                'status' => Quiz::STATUS_DRAFT,
                'publish_quiz' => 1,
            ])
            ->assertRedirect();

        $finalTest = Quiz::query()->where('course_id', $course->id)->where('quiz_type', Quiz::TYPE_FINAL_TEST)->firstOrFail();

        $this->assertNull($finalTest->lesson_id);
        $this->assertSame('Final Test', $finalTest->title);
        $this->assertSame(75, $finalTest->passing_score);
        $this->assertSame(30, $finalTest->time_limit_minutes);
        $this->assertSame(2, $finalTest->max_attempts);
        $this->assertSame(Quiz::STATUS_PUBLISHED, $finalTest->status);
    }

    public function test_instructor_cannot_create_final_test_for_another_instructors_course(): void
    {
        [, $course] = $this->courseScenario();
        $otherInstructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'wrong-final-owner@example.test');

        $this->actingAs($otherInstructor)
            ->post(route('instructor.final-tests.store', $course), [
                'title' => 'Blocked Final Test',
                'passing_score' => 50,
                'status' => Quiz::STATUS_DRAFT,
            ])
            ->assertForbidden();
    }

    public function test_instructor_can_add_questions_to_final_test(): void
    {
        [$instructor, $course] = $this->courseScenario();
        $finalTest = $this->finalTest($course);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.questions.store', $finalTest), [
                'question_text' => 'Select both correct answers.',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'points' => 2,
                'question_status' => QuizQuestion::STATUS_PUBLISHED,
                'options' => [
                    ['option_text' => 'Correct A'],
                    ['option_text' => 'Wrong'],
                    ['option_text' => 'Correct B'],
                ],
                'correct_option_indexes' => [0, 2],
            ])
            ->assertRedirect();

        $question = $finalTest->questions()->firstOrFail();

        $this->assertSame(3, $question->options()->count());
        $this->assertSame(2, $question->options()->where('is_correct', true)->count());
    }

    public function test_final_test_appears_in_student_learning_workspace_and_uses_guided_mode(): void
    {
        [, $course] = $this->courseScenario();
        $student = $this->enrolledStudent($course);
        $finalTest = $this->finalTest($course);
        $this->singleChoiceQuestion($finalTest);

        $this->actingAs($student)
            ->get(route('student.courses.learn', $course))
            ->assertOk()
            ->assertSee('Final Test')
            ->assertSee('Start Final Test');

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $finalTest))
            ->assertOk()
            ->assertSee('Timer starts only after you press Start Quiz')
            ->assertSee('Start Quiz')
            ->assertDontSee('Correct answer:');
    }

    public function test_student_cannot_take_unpublished_or_inaccessible_final_test(): void
    {
        [, $course] = $this->courseScenario();
        $student = $this->approvedUser(User::ROLE_STUDENT, 'blocked-final-student@example.test');
        $finalTest = $this->finalTest($course, Quiz::STATUS_DRAFT);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $finalTest))
            ->assertNotFound();

        $finalTest->update(['status' => Quiz::STATUS_PUBLISHED]);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $finalTest))
            ->assertForbidden();
    }

    public function test_final_test_result_calculates_and_certificate_template_can_show_final_score(): void
    {
        [, $course] = $this->courseScenario();
        $student = $this->enrolledStudent($course);
        $finalTest = $this->finalTest($course);
        [$correct] = $this->singleChoiceQuestion($finalTest);

        $this->actingAs($student)
            ->post(route('student.quizzes.start', $finalTest))
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $finalTest->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $finalTest, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_id' => $correct->id,
                'finish' => 1,
            ])
            ->assertRedirect();

        $attempt->refresh();
        $this->assertSame(1, $attempt->score);
        $this->assertStringContainsString('Final Test Score', file_get_contents(resource_path('views/certificates/pdf.blade.php')));
    }

    private function courseScenario(): array
    {
        $instructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'final-instructor-'.str()->random(8).'@example.test');
        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Final Module',
            'slug' => 'final-module-'.str()->random(8),
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Final Lesson',
            'slug' => 'final-lesson-'.str()->random(8),
            'lesson_type' => 'text',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$instructor, $course];
    }

    private function finalTest(Course $course, string $status = Quiz::STATUS_PUBLISHED): Quiz
    {
        return Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => 'Final Test',
            'description' => 'Final course assessment.',
            'passing_score' => 50,
            'max_attempts' => 2,
            'time_limit_minutes' => 20,
            'status' => $status,
        ]);
    }

    private function singleChoiceQuestion(Quiz $quiz): array
    {
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is correct?',
            'question_type' => QuizQuestion::TYPE_SINGLE_CHOICE,
            'points' => 1,
            'sort_order' => 1,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);
        $correct = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Correct', 'is_correct' => true, 'sort_order' => 1]);
        $wrong = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Wrong', 'is_correct' => false, 'sort_order' => 2]);

        return [$correct, $wrong];
    }

    private function enrolledStudent(Course $course): User
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'final-student-'.str()->random(8).'@example.test');
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return $student;
    }

    private function approvedUser(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
