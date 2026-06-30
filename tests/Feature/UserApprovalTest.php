<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Livewire\RegisterForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_access_dashboard_normally(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('name', 'Student User')
            ->set('email', 'student-approval@mkscholars.test')
            ->set('role', User::ROLE_STUDENT)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect('/student/dashboard');

        $student = User::where('email', 'student-approval@mkscholars.test')->firstOrFail();

        $this->assertAuthenticatedAs($student);
        $this->assertSame(User::APPROVAL_APPROVED, $student->approval_status);
    }

    public function test_instructor_registration_creates_pending_account(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('name', 'Pending Instructor')
            ->set('email', 'pending-instructor@mkscholars.test')
            ->set('role', User::ROLE_INSTRUCTOR)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('login'));

        $instructor = User::where('email', 'pending-instructor@mkscholars.test')->firstOrFail();

        $this->assertGuest();
        $this->assertSame(User::APPROVAL_PENDING, $instructor->approval_status);
    }

    public function test_mentor_registration_is_temporarily_disabled(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('name', 'Pending Mentor')
            ->set('email', 'pending-mentor@mkscholars.test')
            ->set('role', User::ROLE_MENTOR)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['role']);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'pending-mentor@mkscholars.test']);
    }

    public function test_pending_instructor_cannot_access_instructor_dashboard(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, User::APPROVAL_PENDING, 'pending-dashboard-instructor@mkscholars.test');

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your account is pending admin approval.');

        $this->assertGuest();
    }

    public function test_pending_mentor_workspace_is_temporarily_disabled(): void
    {
        $mentor = $this->user(User::ROLE_MENTOR, User::APPROVAL_PENDING, 'pending-dashboard-mentor@mkscholars.test');

        $this->actingAs($mentor)
            ->get(route('mentor.dashboard'))
            ->assertNotFound();

        $this->assertAuthenticatedAs($mentor);
    }

    public function test_admin_can_approve_instructor_and_instructor_can_access_dashboard(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, User::APPROVAL_APPROVED, 'approval-admin@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, User::APPROVAL_PENDING, 'approve-me-instructor@mkscholars.test');

        $this->actingAs($admin);
        UserResource::setApprovalStatus($instructor, User::APPROVAL_APPROVED);
        $instructor->refresh();

        $this->assertSame(User::APPROVAL_APPROVED, $instructor->approval_status);
        $this->assertNotNull($instructor->approved_at);
        $this->assertSame($admin->id, $instructor->approved_by);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk();
    }

    public function test_admin_can_reject_instructor_and_rejected_instructor_cannot_access_dashboard(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, User::APPROVAL_APPROVED, 'reject-admin@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, User::APPROVAL_PENDING, 'reject-me-instructor@mkscholars.test');

        $this->actingAs($admin);
        UserResource::setApprovalStatus($instructor, User::APPROVAL_REJECTED);
        $instructor->refresh();

        $this->assertSame(User::APPROVAL_REJECTED, $instructor->approval_status);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', $instructor->approvalMessage());
    }

    public function test_admin_cannot_suspend_own_account_through_user_resource_action(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, User::APPROVAL_APPROVED, 'self-protect-admin@mkscholars.test');

        $this->actingAs($admin);
        UserResource::setApprovalStatus($admin, User::APPROVAL_SUSPENDED);
        $admin->refresh();

        $this->assertSame(User::APPROVAL_APPROVED, $admin->approval_status);
        $this->assertTrue($admin->isApproved());
    }

    private function user(string $role, string $approvalStatus, string $email): User
    {
        return User::create([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => $approvalStatus,
            'approved_at' => $approvalStatus === User::APPROVAL_APPROVED ? now() : null,
        ]);
    }
}
