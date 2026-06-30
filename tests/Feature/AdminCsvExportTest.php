<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_students_csv(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'student@mkscholars.test');

        $response = $this->actingAs($admin)->get(route('admin.reports.exports.students'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Name,Email', $content);
        $this->assertStringContainsString($student->email, $content);
    }

    public function test_admin_can_download_each_csv_export(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'all-exports-admin@mkscholars.test');

        foreach ($this->exportRouteNames() as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));

            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
            $this->assertStringContainsString('mk-scholars-', (string) $response->headers->get('content-disposition'));
            $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
            $this->assertStringContainsString('ID,', $response->streamedContent());
        }
    }

    public function test_guest_and_non_admin_users_cannot_download_exports(): void
    {
        $student = $this->user(User::ROLE_STUDENT, 'blocked-student@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'blocked-instructor@mkscholars.test');
        $mentor = $this->user(User::ROLE_MENTOR, 'blocked-mentor@mkscholars.test');

        $this->get(route('admin.reports.exports.payments'))
            ->assertRedirect(route('login'));

        $this->actingAs($student)
            ->get(route('admin.reports.exports.payments'))
            ->assertForbidden();

        $this->actingAs($instructor)
            ->get(route('admin.reports.exports.payments'))
            ->assertForbidden();

        $this->actingAs($mentor)
            ->get(route('admin.reports.exports.payments'))
            ->assertForbidden();
    }

    public function test_payments_csv_excludes_sensitive_file_paths_and_provider_payloads(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'payment-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'payment-student@mkscholars.test');
        $course = $this->course();

        Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 50000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_COURSE,
            'status' => Payment::STATUS_SUBMITTED,
            'reference' => 'SAFE-REFERENCE-001',
            'proof_path' => 'payment-proofs/private-proof.pdf',
            'provider_payload' => ['secret_token' => 'provider-secret-value'],
            'submitted_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.payments'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('SAFE-REFERENCE-001', $content);
        $this->assertStringNotContainsString('payment-proofs/private-proof.pdf', $content);
        $this->assertStringNotContainsString('provider_payload', $content);
        $this->assertStringNotContainsString('provider-secret-value', $content);
    }

    public function test_assignment_submissions_csv_excludes_file_paths(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'assignment-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'assignment-student@mkscholars.test');
        $assignment = $this->assignment();

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Submitted answer',
            'file_path' => 'assignment-submissions/private-answer.pdf',
            'external_link' => 'https://private.example.test/assignment',
            'score' => 88,
            'status' => AssignmentSubmission::STATUS_GRADED,
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.assignment-submissions'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($assignment->title, $content);
        $this->assertStringNotContainsString('assignment-submissions/private-answer.pdf', $content);
        $this->assertStringNotContainsString('private.example.test/assignment', $content);
    }

    public function test_certificates_csv_includes_public_verification_url(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'certificate-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'certificate-student@mkscholars.test');
        $course = $this->course();
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'score' => 92,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.certificates'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(route('certificates.verify', $certificate->verification_code), $content);
        $this->assertStringContainsString($certificate->certificate_number, $content);
    }

    public function test_empty_export_still_includes_headers(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'empty-admin@mkscholars.test');

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.course-reviews'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('ID,Student,"Student Email",Course,Rating,Comment,Status,"Submitted At","Updated At"', $content);
    }

    public function test_csv_escapes_commas_quotes_and_line_breaks(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'escaping-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'escaping-student@mkscholars.test');
        $course = $this->course();

        CourseReview::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => "Helpful, practical \"course\"\nLine two",
            'status' => CourseReview::STATUS_PUBLISHED,
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.course-reviews'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('"Helpful, practical ""course""', $content);
        $this->assertStringContainsString('Line two"', $content);
    }

    public function test_export_requests_do_not_mutate_data(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'readonly-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'readonly-student@mkscholars.test');

        Payment::create([
            'user_id' => $student->id,
            'amount' => 10000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_OTHER,
            'status' => Payment::STATUS_PENDING,
        ]);

        $before = Payment::query()->count();

        $this->actingAs($admin)
            ->get(route('admin.reports.exports.payments'))
            ->assertOk()
            ->streamedContent();

        $this->assertSame($before, Payment::query()->count());
        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_students_export_only_includes_student_users(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'students-only-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'included-student@mkscholars.test');
        $this->user(User::ROLE_INSTRUCTOR, 'excluded-instructor@mkscholars.test');
        $this->user(User::ROLE_MENTOR, 'excluded-mentor@mkscholars.test');

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.students'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($student->email, $content);
        $this->assertStringNotContainsString('excluded-instructor@mkscholars.test', $content);
        $this->assertStringNotContainsString('excluded-mentor@mkscholars.test', $content);
    }

    public function test_quiz_attempt_export_handles_nested_course_data(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'quiz-admin@mkscholars.test');
        $student = $this->user(User::ROLE_STUDENT, 'quiz-student@mkscholars.test');
        $lesson = $this->lesson();
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'CSV Quiz',
            'passing_score' => 70,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 7,
            'total_points' => 10,
            'percentage' => 70,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.exports.quiz-attempts'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('CSV Quiz', $content);
        $this->assertStringContainsString($lesson->module->course->title, $content);
        $this->assertStringContainsString('passed', $content);
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function course(): Course
    {
        $academy = Academy::create([
            'name' => 'CSV Academy '.str()->random(6),
            'slug' => 'csv-academy-'.str()->random(6),
            'summary' => 'CSV academy',
            'description' => 'CSV academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        return Course::create([
            'academy_id' => $academy->id,
            'title' => 'CSV Course '.str()->random(6),
            'slug' => 'csv-course-'.str()->random(8),
            'short_description' => 'CSV course',
            'full_description' => 'CSV course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    private function lesson(): \App\Models\Lesson
    {
        $course = $this->course();
        $module = \App\Models\Module::create([
            'course_id' => $course->id,
            'title' => 'CSV Module '.str()->random(6),
            'slug' => 'csv-module-'.str()->random(8),
            'summary' => 'CSV module',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return \App\Models\Lesson::create([
            'module_id' => $module->id,
            'title' => 'CSV Lesson '.str()->random(6),
            'slug' => 'csv-lesson-'.str()->random(8),
            'lesson_type' => 'text',
            'content' => 'CSV lesson',
            'duration_minutes' => 20,
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ])->load('module.course');
    }

    private function assignment(): Assignment
    {
        $lesson = $this->lesson();

        return Assignment::create([
            'lesson_id' => $lesson->id,
            'title' => 'CSV Assignment '.str()->random(6),
            'instructions' => 'Submit a short answer.',
            'submission_type' => Assignment::TYPE_MIXED,
            'max_score' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);
    }

    private function exportRouteNames(): array
    {
        return [
            'admin.reports.exports.students',
            'admin.reports.exports.enrollments',
            'admin.reports.exports.payments',
            'admin.reports.exports.subscriptions',
            'admin.reports.exports.certificates',
            'admin.reports.exports.quiz-attempts',
            'admin.reports.exports.assignment-submissions',
            'admin.reports.exports.course-reviews',
        ];
    }
}
