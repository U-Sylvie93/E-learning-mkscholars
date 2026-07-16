<?php

namespace Tests\Feature;

use App\Filament\Resources\EntranceExamInstitutions\EntranceExamInstitutionResource;
use App\Filament\Resources\EntranceExamPastPapers\EntranceExamPastPaperResource;
use App\Models\EntranceExamInstitution;
use App\Models\EntranceExamPastPaper;
use App\Models\EntranceExamProgram;
use App\Models\EntranceExamSubject;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntranceExamAcademyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_entrance_exam_foundation_records(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'entrance-admin@mkscholars.test');
        $this->actingAs($admin);

        $institution = EntranceExamInstitution::create([
            'name' => 'Rwanda National University',
            'country' => 'Rwanda',
            'status' => EntranceExamInstitution::STATUS_ACTIVE,
        ]);
        $program = EntranceExamProgram::create([
            'entrance_exam_institution_id' => $institution->id,
            'name' => 'Medicine Faculty',
            'status' => EntranceExamProgram::STATUS_ACTIVE,
        ]);
        $subject = EntranceExamSubject::create([
            'name' => 'Biology',
            'status' => EntranceExamSubject::STATUS_ACTIVE,
        ]);

        $this->assertSame('rwanda-national-university', $institution->slug);
        $this->assertSame($institution->id, $program->institution->id);
        $this->assertSame('biology', $subject->slug);
        $this->assertTrue(EntranceExamInstitutionResource::canCreate());
    }

    public function test_non_admin_cannot_access_filament_management_resources(): void
    {
        foreach ([User::ROLE_STUDENT, User::ROLE_INSTRUCTOR, User::ROLE_VIEWER, User::ROLE_CONTENT_EDITOR] as $role) {
            $this->actingAs($this->user($role, "entrance-{$role}@mkscholars.test"));

            $this->assertFalse(EntranceExamInstitutionResource::canViewAny());
            $this->assertFalse(EntranceExamPastPaperResource::canCreate());
        }
    }

    public function test_past_paper_resource_is_pdf_only(): void
    {
        $resource = str_replace("\r\n", "\n", file_get_contents(app_path('Filament/Resources/EntranceExamPastPapers/EntranceExamPastPaperResource.php')));

        $this->assertStringContainsString("FileUpload::make('paper_file_path')", $resource);
        $this->assertStringContainsString("->acceptedFileTypes(['application/pdf'])", $resource);
        $this->assertStringContainsString('->maxSize(20480)', $resource);
        $this->assertStringContainsString("TextInput::make('price_amount')", $resource);
        $this->assertStringNotContainsString("TextInput::make('title')\n                ->required()", $resource);
    }

    public function test_entrance_exam_admin_name_fields_are_not_required(): void
    {
        $institutionResource = str_replace("\r\n", "\n", file_get_contents(app_path('Filament/Resources/EntranceExamInstitutions/EntranceExamInstitutionResource.php')));
        $programResource = str_replace("\r\n", "\n", file_get_contents(app_path('Filament/Resources/EntranceExamPrograms/EntranceExamProgramResource.php')));
        $subjectResource = str_replace("\r\n", "\n", file_get_contents(app_path('Filament/Resources/EntranceExamSubjects/EntranceExamSubjectResource.php')));

        $this->assertStringNotContainsString("TextInput::make('name')\n                ->required()", $institutionResource);
        $this->assertStringNotContainsString("TextInput::make('name')\n                ->required()", $programResource);
        $this->assertStringNotContainsString("TextInput::make('name')\n                ->required()", $subjectResource);
    }

    public function test_academy_index_shows_published_papers_and_filters_by_classification(): void
    {
        [$student, $paper, $draftPaper] = $this->paperContext();

        $this->get(route('entrance-exam-academy.index'))
            ->assertOk()
            ->assertSee('Entrance Exam Academy')
            ->assertSee($paper->title)
            ->assertSee('5,000 RWF')
            ->assertDontSee($draftPaper->title);

        $this->get(route('entrance-exam-academy.index', [
            'institution' => $paper->entrance_exam_institution_id,
            'program' => $paper->entrance_exam_program_id,
            'subject' => $paper->entrance_exam_subject_id,
            'year' => $paper->exam_year,
        ]))
            ->assertOk()
            ->assertSee($paper->title);
    }

    public function test_paper_detail_has_metadata_and_no_raw_storage_path_or_download_button(): void
    {
        [, $paper] = $this->paperContext();

        $this->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee($paper->title)
            ->assertSee($paper->institution->name)
            ->assertSee($paper->program->name)
            ->assertSee($paper->subject->name)
            ->assertSee('5,000 RWF')
            ->assertSee('Register to Continue')
            ->assertDontSee('Login to Read')
            ->assertDontSee($paper->paper_file_path, false)
            ->assertDontSee('Download');
    }

    public function test_paper_detail_shows_payment_state_actions(): void
    {
        [$student, $paper] = $this->paperContext();

        $this->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Register to Continue')
            ->assertDontSee('Login to Read');

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Pay Now');

        $pending = $this->payment($student, $paper, Payment::STATUS_SUBMITTED);

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Payment Pending');

        $pending->update(['status' => Payment::STATUS_REJECTED]);

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Pay Again');

        $pending->update(['status' => Payment::STATUS_APPROVED]);

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Read Paper');
    }

    public function test_pay_now_reuses_existing_pending_payment(): void
    {
        [$student, $paper] = $this->paperContext();
        $payment = $this->payment($student, $paper, Payment::STATUS_PENDING);

        $this->actingAs($student)
            ->post(route('entrance-exam-academy.papers.pay', $paper))
            ->assertRedirect(route('student.payments.show', $payment));

        $this->assertSame(1, Payment::query()
            ->where('user_id', $student->id)
            ->where('purpose', Payment::PURPOSE_ENTRANCE_EXAM)
            ->where('entrance_exam_past_paper_id', $paper->id)
            ->count());
    }

    public function test_pay_now_uses_admin_set_paper_price(): void
    {
        [$student, $paper] = $this->paperContext();
        $paper->update([
            'price_amount' => 7500,
            'currency' => 'RWF',
        ]);

        $this->actingAs($student)
            ->post(route('entrance-exam-academy.papers.pay', $paper))
            ->assertRedirect();

        $payment = Payment::query()
            ->where('user_id', $student->id)
            ->where('entrance_exam_past_paper_id', $paper->id)
            ->where('purpose', Payment::PURPOSE_ENTRANCE_EXAM)
            ->firstOrFail();

        $this->assertSame('7500.00', $payment->amount);
        $this->assertSame('RWF', $payment->currency);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
    }

    public function test_free_paper_can_be_read_by_authenticated_student_without_payment(): void
    {
        Storage::fake('public');
        [$student, $paper] = $this->paperContext();
        $paper->update(['price_amount' => 0]);
        Storage::disk('public')->put($paper->paper_file_path, '%PDF-1.4 free entrance paper');

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.show', $paper))
            ->assertOk()
            ->assertSee('Free')
            ->assertSee('Read Paper')
            ->assertDontSee('Pay Now');

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.inline', $paper))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_viewer_requires_login_and_approved_payment_without_warning_text_or_download_button(): void
    {
        [$student, $paper] = $this->paperContext();

        $this->get(route('entrance-exam-academy.papers.view', $paper))
            ->assertRedirect(route('login'));

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.view', $paper))
            ->assertRedirect(route('entrance-exam-academy.papers.show', $paper))
            ->assertSessionHasErrors('payment');

        $this->payment($student, $paper, Payment::STATUS_APPROVED);

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.view', $paper))
            ->assertOk()
            ->assertSee('data-testid="entrance-exam-pdf-viewer"', false)
            ->assertSee(route('entrance-exam-academy.papers.inline', $paper), false)
            ->assertSee('data-pdf-dark', false)
            ->assertSee('data-pdf-zoom-in', false)
            ->assertSee('data-pdf-zoom-out', false)
            ->assertSee('data-pdf-zoom-reset', false)
            ->assertDontSee('Read-only viewing reduces easy downloading')
            ->assertDontSee('screen recording')
            ->assertDontSee($paper->paper_file_path, false)
            ->assertDontSee('Download');
    }

    public function test_approved_paid_user_can_view_published_pdf_inline(): void
    {
        Storage::fake('public');
        [$student, $paper] = $this->paperContext();
        Storage::disk('public')->put($paper->paper_file_path, '%PDF-1.4 entrance paper');
        $this->payment($student, $paper, Payment::STATUS_APPROVED);

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.inline', $paper))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$paper->slug.'.pdf"');
    }

    public function test_draft_paper_detail_and_pdf_cannot_be_viewed_by_changing_url(): void
    {
        [$student, , $draftPaper] = $this->paperContext();
        $this->payment($student, $draftPaper, Payment::STATUS_APPROVED);

        $this->get(route('entrance-exam-academy.papers.show', $draftPaper))->assertNotFound();

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.inline', $draftPaper))
            ->assertNotFound();
    }

    public function test_inline_route_rejects_missing_or_non_pdf_files_without_exposing_paths(): void
    {
        Storage::fake('public');
        [$student, $paper] = $this->paperContext();
        $this->payment($student, $paper, Payment::STATUS_APPROVED);
        $paper->update([
            'paper_file_path' => 'entrance-exam/past-papers/not-a-pdf.txt',
            'paper_file_mime' => 'text/plain',
        ]);
        Storage::disk('public')->put($paper->paper_file_path, 'not pdf');

        $this->actingAs($student)
            ->get(route('entrance-exam-academy.papers.inline', $paper))
            ->assertNotFound();
    }

    private function paperContext(): array
    {
        Storage::fake('public');
        $student = $this->user(User::ROLE_STUDENT, 'entrance-student-'.str()->random(6).'@mkscholars.test');
        $admin = $this->user(User::ROLE_ADMIN, 'entrance-uploader-'.str()->random(6).'@mkscholars.test');
        $institution = EntranceExamInstitution::create([
            'name' => 'Entrance Test University',
            'country' => 'Rwanda',
            'status' => EntranceExamInstitution::STATUS_ACTIVE,
        ]);
        $program = EntranceExamProgram::create([
            'entrance_exam_institution_id' => $institution->id,
            'name' => 'Engineering Faculty',
            'status' => EntranceExamProgram::STATUS_ACTIVE,
        ]);
        $subject = EntranceExamSubject::create([
            'name' => 'Mathematics',
            'status' => EntranceExamSubject::STATUS_ACTIVE,
        ]);
        Storage::disk('public')->put('entrance-exam/past-papers/math-2025.pdf', '%PDF-1.4');
        $paper = EntranceExamPastPaper::create([
            'entrance_exam_institution_id' => $institution->id,
            'entrance_exam_program_id' => $program->id,
            'entrance_exam_subject_id' => $subject->id,
            'title' => 'Mathematics Entrance Paper 2025',
            'description' => 'Preparation paper.',
            'exam_year' => 2025,
            'exam_type' => 'National entrance',
            'paper_file_path' => 'entrance-exam/past-papers/math-2025.pdf',
            'paper_file_disk' => 'public',
            'paper_file_mime' => 'application/pdf',
            'price_amount' => 5000,
            'currency' => 'RWF',
            'status' => EntranceExamPastPaper::STATUS_PUBLISHED,
            'uploaded_by' => $admin->id,
        ]);
        $draftPaper = EntranceExamPastPaper::create([
            'title' => 'Draft Hidden Paper',
            'paper_file_path' => 'entrance-exam/past-papers/draft.pdf',
            'paper_file_disk' => 'public',
            'paper_file_mime' => 'application/pdf',
            'price_amount' => 5000,
            'currency' => 'RWF',
            'status' => EntranceExamPastPaper::STATUS_DRAFT,
            'uploaded_by' => $admin->id,
        ]);

        return [$student, $paper, $draftPaper];
    }

    private function payment(User $student, EntranceExamPastPaper $paper, string $status): Payment
    {
        return Payment::create([
            'user_id' => $student->id,
            'entrance_exam_past_paper_id' => $paper->id,
            'amount' => 5000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_ENTRANCE_EXAM,
            'provider' => Payment::PROVIDER_MANUAL,
            'status' => $status,
            'submitted_at' => in_array($status, [Payment::STATUS_SUBMITTED, Payment::STATUS_APPROVED, Payment::STATUS_REJECTED], true) ? now() : null,
            'reviewed_at' => in_array($status, [Payment::STATUS_APPROVED, Payment::STATUS_REJECTED], true) ? now() : null,
        ]);
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline().' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
