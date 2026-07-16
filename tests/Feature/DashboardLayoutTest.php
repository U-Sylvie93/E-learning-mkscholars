<?php

namespace Tests\Feature;

use App\Livewire\LoginForm;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_approved_users_to_their_role_workspaces(): void
    {
        $users = [
            [User::ROLE_STUDENT, 'student-login-redirect@mkscholars.test', '/student/dashboard'],
            [User::ROLE_INSTRUCTOR, 'instructor-login-redirect@mkscholars.test', '/instructor/dashboard'],
            [User::ROLE_MENTOR, 'mentor-login-redirect@mkscholars.test', '/mentor/dashboard'],
            [User::ROLE_ADMIN, 'admin-login-redirect@mkscholars.test', '/admin'],
        ];

        foreach ($users as [$role, $email, $path]) {
            $user = $this->approvedUser($role, $email);

            Livewire::test(LoginForm::class)
                ->set('email', $email)
                ->set('password', 'password')
                ->call('login')
                ->assertRedirect($path);

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_pending_instructor_and_mentor_are_blocked_at_login(): void
    {
        foreach ([User::ROLE_INSTRUCTOR, User::ROLE_MENTOR] as $role) {
            $this->user($role, User::APPROVAL_PENDING, "pending-login-{$role}@mkscholars.test");

            Livewire::test(LoginForm::class)
                ->set('email', "pending-login-{$role}@mkscholars.test")
                ->set('password', 'password')
                ->call('login')
                ->assertHasErrors(['email']);

            $this->assertGuest();
        }
    }

    public function test_active_role_dashboards_render_shared_dashboard_shell_without_public_footer(): void
    {
        $dashboards = [
            [User::ROLE_STUDENT, 'student.dashboard', 'Student workspace', 'Continue Learning'],
            [User::ROLE_INSTRUCTOR, 'instructor.dashboard', 'Instructor workspace', 'View Courses'],
        ];

        foreach ($dashboards as [$role, $route, $workspaceLabel, $primaryAction]) {
            $user = $this->approvedUser($role, "{$role}-dashboard-shell@mkscholars.test");

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-testid="dashboard-shell"', false)
                ->assertSee('data-testid="dashboard-sidebar"', false)
                ->assertSee('data-testid="dashboard-sidebar-toggle"', false)
                ->assertSee('data-testid="dashboard-nav-item"', false)
                ->assertSee('data-testid="dashboard-sidebar-collapsed-icon"', false)
                ->assertSee('data-testid="dashboard-topbar"', false)
                ->assertSee('mk-dashboard-content', false)
                ->assertSee($workspaceLabel)
                ->assertSee('Notifications')
                ->assertSee('Settings')
                ->assertDontSee('Settings soon')
                ->assertSee('Logout')
                ->assertSee($primaryAction)
                ->assertDontSee('Quick links')
                ->assertDontSee('Premium learning support');
        }
    }

    public function test_student_sidebar_hides_disabled_navigation_items(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-sidebar-hidden@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="dashboard-sidebar"', false)
            ->assertSee('data-testid="dashboard-sidebar-toggle"', false)
            ->assertSee('data-testid="dashboard-nav-item"', false)
            ->assertSee('data-testid="dashboard-sidebar-collapsed-icon"', false)
            ->assertSee('My Courses')
            ->assertSee('Assignments')
            ->assertSee('Certificates')
            ->assertSee('Payments')
            ->assertSee('Documents')
            ->assertSee('Live Classes')
            ->assertSee('Entrance Exam')
            ->assertSee(route('entrance-exam-academy.index'), false)
            ->assertDontSee('Mentorship')
            ->assertDontSee('Subscriptions')
            ->assertDontSee('Opportunities');
    }

    public function test_mobile_dashboard_menu_is_scrollable_and_keeps_account_actions(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-mobile-menu@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="dashboard-mobile-drawer"', false)
            ->assertSee('max-h-[calc(100vh-5rem)]', false)
            ->assertSee('overflow-y-auto', false)
            ->assertSee('Settings')
            ->assertSee('Back to Site')
            ->assertSee('Logout');
    }

    public function test_student_dashboard_uses_responsive_card_grid_markers(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-card-grid@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="student-dashboard-grid"', false)
            ->assertSee('data-testid="student-dashboard-card"', false)
            ->assertSee('md:grid-cols-2 xl:grid-cols-3', false)
            ->assertDontSee('lg:grid-cols-7', false);
    }

    public function test_representative_workspace_pages_use_dashboard_shell(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-linked-shell@mkscholars.test');
        $instructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'instructor-linked-shell@mkscholars.test');

        $studentRoutes = ['student.my-courses', 'student.assignments', 'student.payments', 'student.notifications', 'student.settings'];
        foreach ($studentRoutes as $route) {
            $this->actingAs($student)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-testid="dashboard-shell"', false)
                ->assertSee('mk-dashboard-content', false)
                ->assertDontSee('Premium learning support');
        }

        foreach (['instructor.live-classes.index', 'instructor.notifications', 'instructor.settings'] as $route) {
            $this->actingAs($instructor)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-testid="dashboard-shell"', false)
                ->assertSee('mk-dashboard-content', false)
                ->assertDontSee('Premium learning support');
        }
    }

    public function test_mentorship_routes_are_temporarily_disabled_for_approved_mentors_and_students(): void
    {
        $mentor = $this->approvedUser(User::ROLE_MENTOR, 'mentor-disabled-routes@mkscholars.test');
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-disabled-mentorship@mkscholars.test');

        foreach (['mentor.dashboard', 'mentor.students', 'mentor.check-ins', 'mentor.notifications', 'mentor.settings'] as $route) {
            $this->actingAs($mentor)
                ->get(route($route))
                ->assertNotFound();
        }

        $this->actingAs($student)
            ->get(route('student.mentorship'))
            ->assertNotFound();
    }

    public function test_notification_badge_is_hidden_when_unread_count_is_zero(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-zero-badge@mkscholars.test');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('data-testid="notification-badge"', false);
    }

    public function test_notification_badge_appears_and_caps_high_counts(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-badge-count@mkscholars.test');

        foreach (range(1, 105) as $index) {
            AppNotification::create([
                'user_id' => $student->id,
                'title' => 'Unread update '.$index,
                'message' => 'Dashboard notification badge test.',
                'type' => AppNotification::TYPE_INFO,
                'category' => AppNotification::CATEGORY_SYSTEM,
            ]);
        }

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="notification-badge"', false)
            ->assertSee('99+');
    }

    public function test_notification_badge_updates_after_marking_notification_read(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-read-badge@mkscholars.test');
        $notification = AppNotification::create([
            'user_id' => $student->id,
            'title' => 'Read me',
            'message' => 'This notification should be marked read.',
            'type' => AppNotification::TYPE_REMINDER,
            'category' => AppNotification::CATEGORY_SYSTEM,
        ]);

        $this->actingAs($student)
            ->get(route('student.notifications'))
            ->assertOk()
            ->assertSee('data-testid="notification-badge"', false)
            ->assertSee('Mark Read');

        $this->actingAs($student)
            ->post(route('student.notifications.read', $notification))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.notifications'))
            ->assertOk()
            ->assertDontSee('data-testid="notification-badge"', false);
    }

    public function test_dashboard_routes_remain_role_protected(): void
    {
        $student = $this->approvedUser(User::ROLE_STUDENT, 'student-role-block@mkscholars.test');
        $instructor = $this->approvedUser(User::ROLE_INSTRUCTOR, 'instructor-role-block@mkscholars.test');
        $mentor = $this->approvedUser(User::ROLE_MENTOR, 'mentor-role-block@mkscholars.test');

        $this->actingAs($student)->get(route('instructor.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('mentor.dashboard'))->assertForbidden();
        $this->actingAs($instructor)->get(route('student.dashboard'))->assertForbidden();
        $this->actingAs($mentor)->get(route('instructor.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_role_dashboards(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('instructor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('mentor.dashboard'))->assertRedirect(route('login'));
    }

    private function approvedUser(string $role, string $email): User
    {
        return $this->user($role, User::APPROVAL_APPROVED, $email);
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
