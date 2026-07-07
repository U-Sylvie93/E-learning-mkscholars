<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizBuilderPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_page_loads_for_enrolled_student_with_questions_and_options(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('data-testid="quiz-instructions"', false)
            ->assertSee($quiz->title)
            ->assertSee('Timer starts only after you press Start Quiz')
            ->assertSee('Passing Score')
            ->assertSee('Start Quiz')
            ->assertDontSee($question->question_text)
            ->assertDontSee($correctOption->option_text);
    }

    public function test_unenrolled_student_cannot_open_or_submit_quiz(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario(enroll: false);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertForbidden();
    }

    public function test_required_quiz_answer_validation_works(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [],
            ])
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('answers.'.$question->id);
    }

    public function test_student_can_submit_quiz_and_see_result_review(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();

        $response = $this->actingAs($student)
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->first();

        $this->assertNotNull($attempt);
        $this->assertSame(QuizAttempt::STATUS_PASSED, $attempt->status);
        $this->assertSame(100, $attempt->percentage);
        $response->assertRedirect(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]));

        $this->actingAs($student)
            ->get(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]))
            ->assertOk()
            ->assertSee('Quiz result')
            ->assertSee('Answer review')
            ->assertSee('1/1')
            ->assertSee('100%')
            ->assertSee('Correct');
    }

    public function test_answer_option_from_another_question_is_rejected(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();
        [, , , , , $otherOption] = $this->createQuizScenario();

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $otherOption->id],
            ])
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('answers.'.$question->id);
    }

    public function test_student_cannot_view_another_students_quiz_result(): void
    {
        [$student, $course, , $quiz, $question, $correctOption] = $this->createQuizScenario();
        $otherStudent = User::create([
            'name' => 'Other Quiz Student',
            'email' => 'other-quiz-student@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        Enrollment::create([
            'user_id' => $otherStudent->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 1,
            'total_points' => 1,
            'percentage' => 100,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'quiz_option_id' => $correctOption->id,
            'is_correct' => true,
            'points_awarded' => 1,
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]))
            ->assertOk()
            ->assertDontSee('Quiz result')
            ->assertSee('Start Quiz');
    }

    public function test_learning_page_quiz_card_shows_question_count_and_attempt_status(): void
    {
        [$student, $course, $lesson, $quiz] = $this->createQuizScenario();
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 1,
            'total_points' => 1,
            'percentage' => 100,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee($quiz->title)
            ->assertSee('1 questions')
            ->assertSee('Latest: 100% passed')
            ->assertSee('Review or Retake Quiz');
    }

    public function test_quiz_with_missing_options_does_not_crash_and_blocks_submit(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();
        $question->options()->delete();

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Start Quiz');

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('quiz');

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => 9999],
            ])
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('quiz');
    }


    public function test_true_false_quiz_submission_scores_correctly(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();
        $question->update(['question_type' => QuizQuestion::TYPE_TRUE_FALSE]);
        $question->options()->delete();
        $trueOption = QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $trueOption->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 1,
            'total_points' => 1,
            'percentage' => 100,
            'status' => QuizAttempt::STATUS_PASSED,
        ]);
    }

    public function test_quiz_without_published_questions_shows_clean_state_and_blocks_submit(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();
        $question->update(['status' => QuizQuestion::STATUS_DRAFT]);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('0')
            ->assertSee('Start Quiz');

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('quiz');

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.submit', $quiz), ['answers' => []])
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('quiz');
    }

    public function test_unpublished_quiz_cannot_be_opened_or_submitted(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();
        $quiz->update(['status' => Quiz::STATUS_DRAFT]);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertNotFound();

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertNotFound();
    }

    public function test_zero_point_question_does_not_break_scoring(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();
        $question->update(['points' => 0]);

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total_points' => 0,
            'percentage' => 0,
            'status' => QuizAttempt::STATUS_FAILED,
        ]);
    }

    public function test_deleted_selected_option_does_not_crash_result_page(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total_points' => 1,
            'percentage' => 0,
            'status' => QuizAttempt::STATUS_FAILED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'quiz_option_id' => $correctOption->id,
            'is_correct' => false,
            'points_awarded' => 0,
        ]);
        $correctOption->delete();

        $this->actingAs($student)
            ->get(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]))
            ->assertOk()
            ->assertSee('Quiz result')
            ->assertSee('No answer selected');
    }

    public function test_answer_key_is_not_visible_before_submission(): void
    {
        [$student, , , $quiz] = $this->createQuizScenario();

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertOk()
            ->assertDontSee('Answer review')
            ->assertDontSee('Correct answer:')
            ->assertDontSee('Incorrect')
            ->assertDontSee($quiz->questions()->first()->options()->where('is_correct', true)->first()->option_text);
    }

    public function test_answer_for_another_quiz_question_is_rejected(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createQuizScenario();
        [, , , , $otherQuestion] = $this->createQuizScenario();

        $this->actingAs($student)
            ->from(route('student.quizzes.show', $quiz))
            ->post(route('student.quizzes.submit', $quiz), [
                'answers' => [
                    $question->id => $correctOption->id,
                    $otherQuestion->id => $correctOption->id,
                ],
            ])
            ->assertRedirect(route('student.quizzes.show', $quiz))
            ->assertSessionHasErrors('answers');
    }

    public function test_learning_page_quiz_card_handles_no_attempt(): void
    {
        [$student, $course, $lesson, $quiz] = $this->createQuizScenario();

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee($quiz->title)
            ->assertSee('1 questions')
            ->assertSee('Ready')
            ->assertSee('Start Quiz');
    }

    public function test_quiz_questions_and_options_display_in_sort_order(): void
    {
        [$student, , , $quiz, $question] = $this->createQuizScenario();
        $question->options()->delete();
        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Second option',
            'is_correct' => false,
            'sort_order' => 2,
        ]);
        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'First option',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        $laterQuestion = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Second ordered question?',
            'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'points' => 1,
            'sort_order' => 2,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);
        QuizOption::create([
            'quiz_question_id' => $laterQuestion->id,
            'option_text' => 'Later answer',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->assertOk()
            ->assertSeeInOrder([
                $question->question_text,
                'First option',
                'Second option',
            ])
            ->assertDontSee($laterQuestion->question_text);
    }
    private function createQuizScenario(bool $enroll = true): array
    {
        $student = User::create([
            'name' => 'Quiz Student',
            'email' => 'quiz-student-'.str()->random(8).'@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Quiz Module',
            'slug' => 'quiz-module-'.str()->random(8),
            'summary' => 'Module summary.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Quiz Lesson',
            'slug' => 'quiz-lesson-'.str()->random(8),
            'lesson_type' => 'quiz',
            'content' => 'Lesson content.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Knowledge Check',
            'description' => 'Choose the best answer.',
            'passing_score' => 50,
            'max_attempts' => 2,
            'time_limit_minutes' => 15,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is the safest answer?',
            'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'points' => 1,
            'sort_order' => 1,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);
        $correctOption = QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'The correct option',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'The incorrect option',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        if ($enroll) {
            Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        return [$student, $course, $lesson, $quiz, $question, $correctOption];
    }
}
