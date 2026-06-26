<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_settings_page_loads(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'settings-student@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.settings'))
            ->assertOk()
            ->assertSee('Profile settings')
            ->assertSee($student->name)
            ->assertSee($student->email)
            ->assertDontSee('Settings soon');
    }

    public function test_approved_instructor_settings_page_loads(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, User::APPROVAL_APPROVED, 'settings-instructor@mkscholars.test');

        $this->actingAs($instructor)
            ->get(route('instructor.settings'))
            ->assertOk()
            ->assertSee('Instructor account')
            ->assertSee('Approved');
    }

    public function test_approved_mentor_settings_page_loads(): void
    {
        $mentor = $this->user(User::ROLE_MENTOR, User::APPROVAL_APPROVED, 'settings-mentor@mkscholars.test');

        $this->actingAs($mentor)
            ->get(route('mentor.settings'))
            ->assertOk()
            ->assertSee('Mentor account')
            ->assertSee('Approved');
    }

    public function test_guest_is_redirected_from_settings_pages(): void
    {
        $this->get(route('student.settings'))->assertRedirect(route('login'));
        $this->get(route('instructor.settings'))->assertRedirect(route('login'));
        $this->get(route('mentor.settings'))->assertRedirect(route('login'));
    }

    public function test_wrong_roles_are_blocked_from_settings_pages(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'wrong-role-student-settings@mkscholars.test');
        $instructor = $this->user(User::ROLE_INSTRUCTOR, User::APPROVAL_APPROVED, 'wrong-role-instructor-settings@mkscholars.test');
        $mentor = $this->user(User::ROLE_MENTOR, User::APPROVAL_APPROVED, 'wrong-role-mentor-settings@mkscholars.test');

        $this->actingAs($student)->get(route('instructor.settings'))->assertForbidden();
        $this->actingAs($student)->get(route('mentor.settings'))->assertForbidden();
        $this->actingAs($instructor)->get(route('student.settings'))->assertForbidden();
        $this->actingAs($mentor)->get(route('instructor.settings'))->assertForbidden();
    }

    public function test_unapproved_instructor_and_mentor_settings_are_blocked(): void
    {
        foreach ([User::APPROVAL_PENDING, User::APPROVAL_REJECTED, User::APPROVAL_SUSPENDED] as $status) {
            $instructor = $this->user(User::ROLE_INSTRUCTOR, $status, "{$status}-instructor-settings@mkscholars.test");

            $this->actingAs($instructor)
                ->get(route('instructor.settings'))
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', $instructor->approvalMessage());

            $mentor = $this->user(User::ROLE_MENTOR, $status, "{$status}-mentor-settings@mkscholars.test");

            $this->actingAs($mentor)
                ->get(route('mentor.settings'))
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', $mentor->approvalMessage());
        }
    }

    public function test_user_can_update_own_name_only(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'profile-update-settings@mkscholars.test');
        $other = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'profile-other-settings@mkscholars.test');

        $this->actingAs($student)
            ->post(route('student.settings.profile'), [
                'name' => 'Updated Student Name',
                'user_id' => $other->id,
                'email' => 'changed-email@mkscholars.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('profile_status');

        $student->refresh();
        $other->refresh();

        $this->assertSame('Updated Student Name', $student->name);
        $this->assertSame('profile-update-settings@mkscholars.test', $student->email);
        $this->assertNotSame('Updated Student Name', $other->name);
    }

    public function test_profile_name_is_required(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'profile-validation-settings@mkscholars.test');

        $this->actingAs($student)
            ->from(route('student.settings'))
            ->post(route('student.settings.profile'), ['name' => ''])
            ->assertRedirect(route('student.settings'))
            ->assertSessionHasErrors('name');
    }

    public function test_password_change_works_with_correct_current_password(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'password-settings@mkscholars.test');

        $this->actingAs($student)
            ->post(route('student.settings.password'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect()
            ->assertSessionHas('password_status');

        $this->assertTrue(Hash::check('new-password-123', $student->refresh()->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'wrong-password-settings@mkscholars.test');
        $oldPassword = $student->password;

        $this->actingAs($student)
            ->from(route('student.settings'))
            ->post(route('student.settings.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('student.settings'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($oldPassword, $student->refresh()->password);
    }

    public function test_dashboard_sidebar_settings_link_appears(): void
    {
        $student = $this->user(User::ROLE_STUDENT, User::APPROVAL_APPROVED, 'sidebar-settings@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee(route('student.settings'), false)
            ->assertDontSee('Settings soon');
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

