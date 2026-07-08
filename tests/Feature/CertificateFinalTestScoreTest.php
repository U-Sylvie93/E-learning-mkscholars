<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CertificateFinalTestScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_uses_best_submitted_final_test_score(): void
    {
        [$student, $course] = $this->courseScenario();
        $finalTest = $this->finalTest($course);

        $this->submittedAttempt($student, $finalTest, 64);
        $this->submittedAttempt($student, $finalTest, 88);
        $this->submittedAttempt($student, $finalTest, 72);

        $certificate = $this->certificate($student, $course, ['score' => null]);

        $this->assertSame(88, $certificate->score);
        $this->assertSame(88, $certificate->displayScore());
    }

    public function test_certificate_falls_back_to_existing_score_without_final_test_attempt(): void
    {
        [$student, $course] = $this->courseScenario();

        $certificate = $this->certificate($student, $course, ['score' => 74]);

        $this->assertSame(74, $certificate->score);
        $this->assertSame(74, $certificate->displayScore());
    }

    public function test_certificate_can_calculate_percentage_from_raw_attempt_points(): void
    {
        [$student, $course] = $this->courseScenario();
        $finalTest = $this->finalTest($course);

        QuizAttempt::create([
            'quiz_id' => $finalTest->id,
            'user_id' => $student->id,
            'score' => 9,
            'total_points' => 10,
            'percentage' => null,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $certificate = $this->certificate($student, $course, ['score' => null]);

        $this->assertSame(90, $certificate->score);
    }

    public function test_student_certificate_page_shows_final_test_score_without_answer_review(): void
    {
        [$student, $course] = $this->courseScenario();
        $finalTest = $this->finalTest($course);
        $this->submittedAttempt($student, $finalTest, 91);
        $certificate = $this->certificate($student, $course, ['score' => null]);

        $this->actingAs($student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Final Test Score')
            ->assertSee('91%')
            ->assertDontSee('Answer review')
            ->assertDontSee('Correct answer:');
    }

    public function test_public_verification_shows_final_test_score_only_for_valid_issued_certificate(): void
    {
        [$student, $course] = $this->courseScenario();
        $finalTest = $this->finalTest($course);
        $this->submittedAttempt($student, $finalTest, 83);

        $issuedCertificate = $this->certificate($student, $course, ['score' => null]);
        $revokedCertificate = $this->certificate($student, $course, [
            'score' => null,
            'status' => Certificate::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        $this->get(route('certificates.verify', $issuedCertificate->verification_code))
            ->assertOk()
            ->assertSee('Certificate Verified')
            ->assertSee('Final Test Score')
            ->assertSee('83%')
            ->assertDontSee('Answer review')
            ->assertDontSee('Correct answer:');

        $this->get(route('certificates.verify', $revokedCertificate->verification_code))
            ->assertOk()
            ->assertSee('Certificate Not Valid')
            ->assertDontSee('Final Test Score')
            ->assertDontSee('83%');
    }

    public function test_another_student_cannot_view_certificate_or_mark(): void
    {
        [$student, $course] = $this->courseScenario();
        $otherStudent = $this->student('other-certificate-student@example.test');
        $finalTest = $this->finalTest($course);
        $this->submittedAttempt($student, $finalTest, 79);
        $certificate = $this->certificate($student, $course, ['score' => null]);

        $this->actingAs($otherStudent)
            ->get(route('student.certificates.show', $certificate))
            ->assertForbidden();
    }

    public function test_admin_duplicate_certificate_guard_still_blocks_second_issued_certificate(): void
    {
        [$student, $course] = $this->courseScenario();
        $this->certificate($student, $course, ['score' => 85]);

        $this->expectException(ValidationException::class);

        CertificateResource::normalizeCertificateData([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'score' => null,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
    }

    private function courseScenario(): array
    {
        $student = $this->student('certificate-final-score-'.str()->random(8).'@example.test');
        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);

        return [$student, $course];
    }

    private function finalTest(Course $course): Quiz
    {
        return Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => 'Final Test',
            'description' => 'Final course assessment.',
            'passing_score' => 50,
            'max_attempts' => 3,
            'time_limit_minutes' => 30,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
    }

    private function submittedAttempt(User $student, Quiz $quiz, int $percentage): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => $percentage,
            'total_points' => 100,
            'percentage' => $percentage,
            'status' => $percentage >= $quiz->passing_score ? QuizAttempt::STATUS_PASSED : QuizAttempt::STATUS_FAILED,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
        ]);
    }

    private function certificate(User $student, Course $course, array $overrides = []): Certificate
    {
        return Certificate::create(array_merge([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ], $overrides));
    }

    private function student(string $email): User
    {
        return User::create([
            'name' => 'Certificate Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
