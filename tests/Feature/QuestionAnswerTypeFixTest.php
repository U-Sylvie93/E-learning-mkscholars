<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
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

class QuestionAnswerTypeFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_answer_short_answer_quiz_question_without_options(): void
    {
        [$student, , $course, $lesson] = $this->learningContext('quiz-short-answer');
        $quiz = $this->quiz($course, $lesson);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What did you learn?',
            'question_type' => QuizQuestion::TYPE_SHORT_ANSWER,
            'points' => 5,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);

        $this->actingAs($student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->assertOk()
            ->assertSee('Short Text Answer')
            ->assertSee('name="answer_text"', false)
            ->assertDontSee('name="option_id"', false);

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'answer_text' => 'I learned how to plan.',
                'finish' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer_text' => 'I learned how to plan.',
            'points_awarded' => 0,
        ]);
        $this->assertSame(QuizAttempt::STATUS_SUBMITTED, $attempt->fresh()->status);
    }

    public function test_true_false_quiz_uses_radio_buttons_and_scores_correctly(): void
    {
        [$student, , $course, $lesson] = $this->learningContext('quiz-true-false');
        $quiz = $this->quiz($course, $lesson);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'MK Scholars is an e-learning platform.',
            'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
            'points' => 2,
            'status' => QuizQuestion::STATUS_PUBLISHED,
        ]);
        $trueOption = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'True', 'is_correct' => true, 'sort_order' => 1]);
        QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'False', 'is_correct' => false, 'sort_order' => 2]);

        $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]))
            ->assertOk()
            ->assertSee('True')
            ->assertSee('False')
            ->assertSee('type="radio"', false)
            ->assertDontSee('type="checkbox"', false);

        $this->actingAs($student)
            ->post(route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => 0]), [
                'option_id' => $trueOption->id,
                'finish' => '1',
            ])
            ->assertRedirect();

        $answer = QuizAnswer::query()->where('quiz_attempt_id', $attempt->id)->firstOrFail();
        $this->assertTrue($answer->is_correct);
        $this->assertSame(2, $answer->points_awarded);
    }

    public function test_assignment_page_renders_text_and_objective_inputs_by_question_type(): void
    {
        [$student, , $course, $lesson] = $this->learningContext('assignment-question-types');
        $assignment = Assignment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Mixed Assignment',
            'instructions' => 'Answer all questions.',
            'submission_type' => Assignment::TYPE_MIXED,
            'max_score' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);
        AssignmentQuestion::create([
            'assignment_id' => $assignment->id,
            'question_text' => 'Short reflection',
            'question_type' => AssignmentQuestion::TYPE_TEXT,
            'is_required' => true,
        ]);
        $trueFalse = AssignmentQuestion::create([
            'assignment_id' => $assignment->id,
            'question_text' => 'Ready to submit?',
            'question_type' => AssignmentQuestion::TYPE_TRUE_FALSE,
            'is_required' => true,
        ]);
        $trueFalse->options()->createMany([
            ['option_text' => 'True', 'is_correct' => true, 'sort_order' => 1],
            ['option_text' => 'False', 'is_correct' => false, 'sort_order' => 2],
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Short reflection')
            ->assertSee('Ready to submit?')
            ->assertSee('name="question_answers['.$trueFalse->id.']"', false)
            ->assertSee('True')
            ->assertSee('False');
    }

    private function learningContext(string $slug): array
    {
        $academy = Academy::create([
            'name' => 'Academy '.$slug,
            'slug' => 'academy-'.$slug,
            'summary' => 'Summary',
            'description' => 'Description',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $instructor = User::create([
            'name' => 'Instructor '.$slug,
            'email' => 'instructor-'.$slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
        $student = User::create([
            'name' => 'Student '.$slug,
            'email' => 'student-'.$slug.'@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'instructor_id' => $instructor->id,
            'title' => 'Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Course summary.',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson '.$slug,
            'slug' => 'lesson-'.$slug,
            'lesson_type' => 'text',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $instructor, $course, $lesson];
    }

    private function quiz(Course $course, Lesson $lesson): Quiz
    {
        return Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Question Type Quiz',
            'description' => 'Question type behavior.',
            'quiz_type' => Quiz::TYPE_LESSON_QUIZ,
            'passing_score' => 50,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }
}
