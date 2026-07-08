<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentQuestionAnswer;
use App\Models\AssignmentOption;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_questions_display_to_enrolled_student(): void
    {
        Storage::fake('public');
        [$student, , , $assignment, $question] = $this->createAssignmentScenario();
        Storage::disk('public')->put($assignment->instruction_file_path, 'Demo assignment document.');

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Assignment questions')
            ->assertSee($question->question_text)
            ->assertSee('Download assignment document');
    }

    public function test_student_can_submit_assignment_question_answers_and_file(): void
    {
        Storage::fake('public');
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [
                    $question->id => 'My structured answer for this assignment.',
                ],
                'submission_file' => UploadedFile::fake()->create('assignment.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('student.assignments.show', $assignment));

        $submission = AssignmentSubmission::query()->where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();

        $this->assertNotNull($submission);
        $this->assertNotNull($submission->file_path);
        Storage::disk('public')->assertExists($submission->file_path);
        $this->assertDatabaseHas('assignment_question_answers', [
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
            'answer' => 'My structured answer for this assignment.',
        ]);
    }

    public function test_required_assignment_question_cannot_be_blank(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);

        $this->actingAs($student)
            ->from(route('student.assignments.show', $assignment))
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [
                    $question->id => '',
                ],
            ])
            ->assertRedirect(route('student.assignments.show', $assignment))
            ->assertSessionHasErrors('question_answers.'.$question->id);
    }

    public function test_existing_file_only_assignment_submission_still_works(): void
    {
        Storage::fake('public');
        [$student, , , $assignment] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_FILE,
        ], createQuestion: false);

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), [
                'submission_file' => UploadedFile::fake()->create('work.zip', 256, 'application/zip'),
            ])
            ->assertRedirect(route('student.assignments.show', $assignment));

        $submission = AssignmentSubmission::query()->where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();

        $this->assertNotNull($submission?->file_path);
        Storage::disk('public')->assertExists($submission->file_path);
    }

    public function test_student_without_course_access_cannot_open_or_submit_assignment(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario(enroll: false);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [$question->id => 'Blocked answer'],
                'text_answer' => 'Blocked text',
            ])
            ->assertForbidden();
    }

    public function test_another_student_cannot_see_existing_submission_answers(): void
    {
        [$student, $course, , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        $otherStudent = User::create([
            'name' => 'Other Student',
            'email' => 'other-assignment-student@example.test',
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

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        AssignmentQuestionAnswer::create([
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
            'answer' => 'Private answer from first student.',
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSee($question->question_text)
            ->assertDontSee('Private answer from first student.');
    }

    public function test_learning_page_assignment_card_shows_questions_and_document(): void
    {
        Storage::fake('public');
        [$student, $course, $lesson, $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        Storage::disk('public')->put($assignment->instruction_file_path, 'Demo assignment document.');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee($assignment->title)
            ->assertSee('1 questions')
            ->assertSee('Document')
            ->assertSee('View assignment document')
            ->assertSee('Open Assignment');
    }

    public function test_instructor_submission_review_shows_question_answers(): void
    {
        Storage::fake('public');
        [$student, $course, , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        $instructor = User::create([
            'name' => 'Assignment Instructor',
            'email' => 'assignment-instructor@example.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        LiveClass::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Instructor assignment review class',
            'meeting_url' => 'https://meet.example.test/class',
            'platform' => 'other',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);
        Storage::disk('public')->put('assignment-submissions/review.pdf', 'review file');
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'assignment-submissions/review.pdf',
            'external_link' => 'https://portfolio.example.test/student/submission-with-a-very-long-url-that-should-wrap-cleanly',
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        AssignmentQuestionAnswer::create([
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
            'answer' => "Instructor-visible structured answer.\nSecond paragraph stays readable.",
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.submissions', $course))
            ->assertOk()
            ->assertSee('data-testid="instructor-submission-card"', false)
            ->assertSee('data-testid="question-answer-card"', false)
            ->assertSee('Question 1')
            ->assertSee('Instructor-visible structured answer.')
            ->assertSee('Second paragraph stays readable.')
            ->assertSee($question->question_text)
            ->assertSee('review.pdf')
            ->assertSee('Download file')
            ->assertSee('Open link')
            ->assertSee('submission-with-a-very-long-url-that-should-wrap-cleanly');
    }
    public function test_admin_assignment_submission_review_uses_structured_panel(): void
    {
        Storage::fake('public');
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        Storage::disk('public')->put('assignment-submissions/admin-review.txt', 'admin review file');
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'text_answer' => "Main text answer\nwith line breaks.",
            'file_path' => 'assignment-submissions/admin-review.txt',
            'external_link' => 'https://example.test/admin-review-link',
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        AssignmentQuestionAnswer::create([
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
            'answer' => 'Admin-visible answer card.',
        ]);
        $admin = User::create([
            'name' => 'Assignment Admin',
            'email' => 'assignment-admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.assignment-submissions.edit', $submission))
            ->assertOk()
            ->assertSee('data-testid="assignment-submission-review-panel"', false)
            ->assertSee('Submission review')
            ->assertSee('Question 1')
            ->assertSee('Admin-visible answer card.')
            ->assertSee('admin-review.txt')
            ->assertSee('Download file')
            ->assertSee('Open link')
            ->assertSee('Grading panel');
    }

    public function test_instructor_submission_review_hides_broken_file_links(): void
    {
        Storage::fake('public');
        [$student, $course, , $assignment] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_FILE,
        ]);
        $instructor = User::create([
            'name' => 'Missing File Instructor',
            'email' => 'missing-file-instructor@example.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        LiveClass::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Missing file review class',
            'meeting_url' => 'https://meet.example.test/class',
            'platform' => 'other',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'assignment-submissions/missing.pdf',
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.submissions', $course))
            ->assertOk()
            ->assertSee('File missing from storage.')
            ->assertDontSee('Download file');
    }
    public function test_missing_instruction_document_does_not_render_broken_links(): void
    {
        Storage::fake('public');
        [$student, $course, $lesson, $assignment] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertDontSee('Download assignment document');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee($assignment->title)
            ->assertDontSee('View assignment document');
    }

    public function test_optional_assignment_question_can_be_blank(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_TEXT,
        ]);
        $question->update(['is_required' => false]);

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), [
                'text_answer' => 'Main answer still satisfies the text assignment.',
                'question_answers' => [
                    $question->id => '',
                ],
            ])
            ->assertRedirect(route('student.assignments.show', $assignment));

        $submission = AssignmentSubmission::query()->where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();

        $this->assertNotNull($submission);
        $this->assertDatabaseMissing('assignment_question_answers', [
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
        ]);
    }

    public function test_unrelated_assignment_question_answer_is_rejected(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        [, , , $otherAssignment, $otherQuestion] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);

        $this->actingAs($student)
            ->from(route('student.assignments.show', $assignment))
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [
                    $question->id => 'Allowed answer.',
                    $otherQuestion->id => 'This question belongs to another assignment.',
                ],
            ])
            ->assertRedirect(route('student.assignments.show', $assignment))
            ->assertSessionHasErrors('question_answers');

        $this->assertDatabaseMissing('assignment_question_answers', [
            'assignment_question_id' => $otherQuestion->id,
            'answer' => 'This question belongs to another assignment.',
        ]);
        $this->assertNotEquals($assignment->id, $otherAssignment->id);
    }

    public function test_invalid_assignment_submission_file_type_is_rejected(): void
    {
        Storage::fake('public');
        [$student, , , $assignment] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_FILE,
        ], createQuestion: false);

        $this->actingAs($student)
            ->from(route('student.assignments.show', $assignment))
            ->post(route('student.assignments.submit', $assignment), [
                'submission_file' => UploadedFile::fake()->create('malware.exe', 12, 'application/x-msdownload'),
            ])
            ->assertRedirect(route('student.assignments.show', $assignment))
            ->assertSessionHasErrors('submission_file');

        $this->assertDatabaseMissing('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_assignment_questions_display_in_sort_order(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario();
        $earlyQuestion = AssignmentQuestion::create([
            'assignment_id' => $assignment->id,
            'question_text' => 'Describe the first planning step.',
            'question_type' => AssignmentQuestion::TYPE_TEXT,
            'points' => 5,
            'sort_order' => 0,
            'is_required' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSeeInOrder([
                $earlyQuestion->question_text,
                $question->question_text,
            ]);
    }

    public function test_student_can_answer_objective_assignment_questions(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        $question->update([
            'question_type' => AssignmentQuestion::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'Choose the correct project steps.',
        ]);
        $firstOption = AssignmentOption::create([
            'assignment_question_id' => $question->id,
            'option_text' => 'Plan the scope',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        $secondOption = AssignmentOption::create([
            'assignment_question_id' => $question->id,
            'option_text' => 'Publish without review',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Choose the correct project steps.')
            ->assertSee('Plan the scope')
            ->assertSee('Publish without review');

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [
                    $question->id => [$firstOption->id, $secondOption->id],
                ],
            ])
            ->assertRedirect(route('student.assignments.show', $assignment));

        $submission = AssignmentSubmission::query()->where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();

        $this->assertNotNull($submission);
        $this->assertDatabaseHas('assignment_question_answers', [
            'assignment_submission_id' => $submission->id,
            'assignment_question_id' => $question->id,
            'answer' => 'Plan the scope, Publish without review',
        ]);
        $this->assertSame(
            [$firstOption->id, $secondOption->id],
            AssignmentQuestionAnswer::query()->where('assignment_submission_id', $submission->id)->first()?->selected_option_ids,
        );
    }

    public function test_assignment_objective_answer_must_belong_to_question(): void
    {
        [$student, , , $assignment, $question] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        $question->update(['question_type' => AssignmentQuestion::TYPE_SINGLE_CHOICE]);
        AssignmentOption::create([
            'assignment_question_id' => $question->id,
            'option_text' => 'Allowed option',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        [, , , , $otherQuestion] = $this->createAssignmentScenario([
            'submission_type' => Assignment::TYPE_MIXED,
        ]);
        $otherOption = AssignmentOption::create([
            'assignment_question_id' => $otherQuestion->id,
            'option_text' => 'Other assignment option',
            'is_correct' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->from(route('student.assignments.show', $assignment))
            ->post(route('student.assignments.submit', $assignment), [
                'question_answers' => [
                    $question->id => $otherOption->id,
                ],
            ])
            ->assertRedirect(route('student.assignments.show', $assignment))
            ->assertSessionHasErrors('question_answers.'.$question->id);

        $this->assertDatabaseMissing('assignment_question_answers', [
            'assignment_question_id' => $question->id,
            'selected_option_ids' => json_encode([$otherOption->id]),
        ]);
    }    private function createAssignmentScenario(array $assignmentOverrides = [], bool $createQuestion = true, bool $enroll = true): array
    {
        $student = User::create([
            'name' => 'Assignment Student',
            'email' => 'assignment-student-'.str()->random(8).'@example.test',
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
            'title' => 'Assignment Module',
            'slug' => 'assignment-module-'.str()->random(8),
            'summary' => 'Module summary.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Assignment Lesson',
            'slug' => 'assignment-lesson-'.str()->random(8),
            'lesson_type' => 'assignment',
            'content' => 'Lesson content.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $assignment = Assignment::create(array_merge([
            'lesson_id' => $lesson->id,
            'title' => 'Structured Assignment',
            'instructions' => 'Complete every required question.',
            'instruction_file_path' => 'assignment-instructions/demo.pdf',
            'submission_type' => Assignment::TYPE_TEXT,
            'max_score' => 100,
            'allow_late_submission' => true,
            'status' => Assignment::STATUS_PUBLISHED,
        ], $assignmentOverrides));
        $question = null;

        if ($createQuestion) {
            $question = AssignmentQuestion::create([
                'assignment_id' => $assignment->id,
                'question_text' => 'Explain your solution approach.',
                'question_type' => AssignmentQuestion::TYPE_TEXTAREA,
                'points' => 10,
                'sort_order' => 1,
                'is_required' => true,
            ]);
        }

        if ($enroll) {
            Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        return [$student, $course, $lesson, $assignment, $question];
    }
}







