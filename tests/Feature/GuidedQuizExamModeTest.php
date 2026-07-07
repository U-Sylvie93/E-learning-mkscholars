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

class GuidedQuizExamModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_instruction_page_loads_before_attempt_starts(): void
    {
        [$student, , , $quiz, $question, $correctOption] = $this->createGuidedQuizScenario();

        $this->actingAs($student)
            ->get(route('student.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('data-testid="quiz-instructions"', false)
            ->assertSee('Start Quiz')
            ->assertSee('Timer starts only after you press Start Quiz')
            ->assertDontSee($question->question_text)
            ->assertDontSee($correctOption->option_text);

        $this->assertDatabaseMissing('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_start_quiz_creates_attempt_with_server_timing(): void
    {
        [$student, , , $quiz] = $this->createGuidedQuizScenario();

        $this->actingAs($student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNotNull($attempt->started_at);
        $this->assertNotNull($attempt->expires_at);
        $this->assertSame(0, $attempt->current_question_index);
    }

    public function test_student_sees_one_question_and_saves_answer_immediately(): void
    {
        [$student, , , $quiz, $firstQuestion, $correctOption, $secondQuestion] = $this->createGuidedQuizScenario(questionCount: 2);
        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $attemptModel = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attemptModel, 'questionIndex' => 0]))
            ->assertOk()
            ->assertSee('data-testid="quiz-exam-mode"', false)
            ->assertSee($firstQuestion->question_text)
            ->assertDontSee($secondQuestion->question_text);

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attemptModel, 'questionIndex' => 0]), [
                'option_id' => $correctOption->id,
            ])
            ->assertRedirect(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attemptModel, 'questionIndex' => 1]));

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attemptModel->id,
            'quiz_question_id' => $firstQuestion->id,
            'quiz_option_id' => $correctOption->id,
            'is_correct' => true,
        ]);
    }

    public function test_option_from_another_question_is_rejected_in_exam_mode(): void
    {
        [$student, , , $quiz, $firstQuestion, , $secondQuestion] = $this->createGuidedQuizScenario(questionCount: 2);
        $otherOption = $secondQuestion->options()->first();

        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->from(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_id' => $otherOption->id,
            ])
            ->assertRedirect(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->assertSessionHasErrors('option_id');

        $this->assertDatabaseMissing('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $firstQuestion->id,
        ]);
    }

    public function test_final_question_submits_and_result_is_private(): void
    {
        [$student, $course, , $quiz, $question, $correctOption] = $this->createGuidedQuizScenario();
        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_id' => $correctOption->id,
                'finish' => '1',
            ])
            ->assertRedirect(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]));

        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_PASSED, $attempt->status);
        $this->assertSame(100, $attempt->percentage);

        $this->actingAs($student)
            ->get(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]))
            ->assertOk()
            ->assertSee('data-testid="quiz-result"', false)
            ->assertSee('Answer review')
            ->assertSee('100%');

        $otherStudent = User::create([
            'name' => 'Other Guided Student',
            'email' => 'other-guided-student@example.test',
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

        $this->actingAs($otherStudent)
            ->get(route('student.quizzes.show', ['quiz' => $quiz, 'attempt' => $attempt->id]))
            ->assertOk()
            ->assertDontSee('Answer review')
            ->assertSee('Start Quiz');
    }

    public function test_expired_attempt_blocks_late_answer_and_submits_saved_work(): void
    {
        [$student, , , $quiz, , $correctOption] = $this->createGuidedQuizScenario();
        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();
        $attempt->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->actingAs($student)
            ->from(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_id' => $correctOption->id,
            ])
            ->assertRedirect(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->assertSessionHasErrors('quiz');

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => QuizAttempt::STATUS_FAILED,
        ]);
        $this->assertDatabaseMissing('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_option_id' => $correctOption->id,
        ]);
    }

    private function createGuidedQuizScenario(int $questionCount = 1): array
    {
        $student = User::create([
            'name' => 'Guided Quiz Student',
            'email' => 'guided-quiz-student-'.str()->random(8).'@example.test',
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
            'title' => 'Guided Quiz Module',
            'slug' => 'guided-quiz-module-'.str()->random(8),
            'summary' => 'Module summary.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Guided Quiz Lesson',
            'slug' => 'guided-quiz-lesson-'.str()->random(8),
            'lesson_type' => 'quiz',
            'content' => 'Prepare carefully.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Guided Knowledge Check',
            'description' => 'Read each question carefully.',
            'passing_score' => 50,
            'max_attempts' => 2,
            'time_limit_minutes' => 15,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $firstQuestion = null;
        $firstCorrectOption = null;
        $secondQuestion = null;

        for ($index = 1; $index <= $questionCount; $index++) {
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_text' => 'Guided question '.$index.'?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'points' => 1,
                'sort_order' => $index,
                'status' => QuizQuestion::STATUS_PUBLISHED,
            ]);
            $correctOption = QuizOption::create([
                'quiz_question_id' => $question->id,
                'option_text' => 'Correct option '.$index,
                'is_correct' => true,
                'sort_order' => 1,
            ]);
            QuizOption::create([
                'quiz_question_id' => $question->id,
                'option_text' => 'Incorrect option '.$index,
                'is_correct' => false,
                'sort_order' => 2,
            ]);

            $firstQuestion ??= $question;
            $firstCorrectOption ??= $correctOption;
            $secondQuestion = $index === 2 ? $question : $secondQuestion;
        }

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $course, $lesson, $quiz, $firstQuestion, $firstCorrectOption, $secondQuestion];
    }
}
