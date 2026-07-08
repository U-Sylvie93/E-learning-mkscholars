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

class InstructorQuizBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_quiz_with_settings_and_multiple_options(): void
    {
        [$instructor, $course, $lesson] = $this->ownedCourseScenario();

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store', $course), [
                'lesson_id' => $lesson->id,
                'title' => 'Phase 41 Knowledge Check',
                'description' => 'Read carefully before starting.',
                'passing_score' => 80,
                'time_limit_minutes' => 25,
                'max_attempts' => 3,
                'status' => Quiz::STATUS_DRAFT,
                'publish_quiz' => 1,
                'question_text' => 'Choose every correct item.',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'points' => 2,
                'question_status' => QuizQuestion::STATUS_PUBLISHED,
                'options' => [
                    ['option_text' => 'Option A'],
                    ['option_text' => 'Option B'],
                    ['option_text' => 'Option C'],
                    ['option_text' => 'Option D'],
                ],
                'correct_option_indexes' => [0, 2],
            ])
            ->assertRedirect();

        $quiz = Quiz::query()->where('title', 'Phase 41 Knowledge Check')->firstOrFail();
        $question = $quiz->questions()->firstOrFail();

        $this->assertSame('Read carefully before starting.', $quiz->description);
        $this->assertSame(25, $quiz->time_limit_minutes);
        $this->assertSame(3, $quiz->max_attempts);
        $this->assertSame(Quiz::STATUS_PUBLISHED, $quiz->status);
        $this->assertSame(4, $question->options()->count());
        $this->assertSame(2, $question->options()->where('is_correct', true)->count());
    }

    public function test_instructor_can_add_single_choice_question_with_one_correct_answer(): void
    {
        [$instructor, , , $quiz] = $this->ownedCourseScenario(withQuiz: true);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.questions.store', $quiz), [
                'question_text' => 'Pick one.',
                'question_type' => QuizQuestion::TYPE_SINGLE_CHOICE,
                'points' => 1,
                'question_status' => QuizQuestion::STATUS_PUBLISHED,
                'options' => [
                    ['option_text' => 'First'],
                    ['option_text' => 'Second'],
                    ['option_text' => 'Third'],
                ],
                'correct_option_index' => 1,
            ])
            ->assertRedirect();

        $question = $quiz->questions()->where('question_text', 'Pick one.')->firstOrFail();

        $this->assertSame(QuizQuestion::TYPE_SINGLE_CHOICE, $question->question_type);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_true_false_question_uses_true_and_false_options(): void
    {
        [$instructor, , , $quiz] = $this->ownedCourseScenario(withQuiz: true);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.questions.store', $quiz), [
                'question_text' => 'The platform is Laravel based.',
                'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
                'points' => 1,
                'question_status' => QuizQuestion::STATUS_PUBLISHED,
                'correct_option_index' => 0,
            ])
            ->assertRedirect();

        $question = $quiz->questions()->where('question_type', QuizQuestion::TYPE_TRUE_FALSE)->firstOrFail();

        $this->assertSame(['True', 'False'], $question->options()->pluck('option_text')->all());
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_instructor_cannot_add_question_to_another_instructors_quiz(): void
    {
        [, , , $quiz] = $this->ownedCourseScenario(withQuiz: true);
        $otherInstructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'wrong-quiz-owner@example.test');

        $this->actingAs($otherInstructor)
            ->post(route('instructor.quizzes.questions.store', $quiz), [
                'question_text' => 'Blocked question.',
                'question_type' => QuizQuestion::TYPE_SINGLE_CHOICE,
                'points' => 1,
                'question_status' => QuizQuestion::STATUS_PUBLISHED,
                'options' => [
                    ['option_text' => 'A'],
                    ['option_text' => 'B'],
                ],
                'correct_option_index' => 0,
            ])
            ->assertForbidden();
    }

    public function test_multiple_choice_scoring_requires_exact_selected_option_set(): void
    {
        [$student, $quiz, $correctA, $correctB, $incorrect] = $this->studentMultipleChoiceScenario();

        $this->actingAs($student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_ids' => [$correctA->id, $incorrect->id],
                'finish' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(0, $attempt->refresh()->score);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total_points' => 1,
            'percentage' => 0,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_question_index' => 0,
        ]);

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_ids' => [$correctA->id, $correctB->id],
                'finish' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, $attempt->refresh()->score);
    }

    private function ownedCourseScenario(bool $withQuiz = false): array
    {
        $instructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'quiz-builder-'.str()->random(8).'@example.test');
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
            'title' => 'Builder Module',
            'slug' => 'builder-module-'.str()->random(8),
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Builder Lesson',
            'slug' => 'builder-lesson-'.str()->random(8),
            'lesson_type' => 'quiz',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $quiz = $withQuiz ? Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Existing Quiz',
            'description' => 'Existing instructions.',
            'passing_score' => 50,
            'status' => Quiz::STATUS_PUBLISHED,
        ]) : null;

        return [$instructor, $course, $lesson, $quiz];
    }

    private function studentMultipleChoiceScenario(): array
    {
        [, $course, $lesson] = $this->ownedCourseScenario();
        $student = $this->approvedUser(User::ROLE_STUDENT, 'multi-student-'.str()->random(8).'@example.test');
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Exact Match Quiz',
            'description' => 'Select all correct answers.',
            'passing_score' => 50,
            'max_attempts' => 3,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Select both correct answers.',
            'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'points' => 1,
            'sort_order' => 1,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);
        $correctA = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Correct A', 'is_correct' => true, 'sort_order' => 1]);
        $correctB = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Correct B', 'is_correct' => true, 'sort_order' => 2]);
        $incorrect = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Incorrect', 'is_correct' => false, 'sort_order' => 3]);

        return [$student, $quiz, $correctA, $correctB, $incorrect];
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
