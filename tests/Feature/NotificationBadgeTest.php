<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_notification_dropdown_hides_badge_when_zero_and_links_to_page(): void
    {
        $student = User::create([
            'name' => 'Notification Student',
            'email' => 'notification-student@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="notification-menu"', false)
            ->assertDontSee('data-testid="notification-badge"', false)
            ->assertSee('No notifications yet')
            ->assertSee(route('student.notifications'), false);
    }

    public function test_dashboard_notification_dropdown_shows_latest_unread_notifications(): void
    {
        $student = User::create([
            'name' => 'Notification Student',
            'email' => 'notification-unread@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        AppNotification::create([
            'user_id' => $student->id,
            'role' => User::ROLE_STUDENT,
            'title' => 'Demo notification visible',
            'message' => 'This notification should appear in the dropdown.',
            'type' => AppNotification::TYPE_INFO,
            'category' => AppNotification::CATEGORY_SYSTEM,
            'action_url' => route('student.notifications'),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="notification-menu"', false)
            ->assertSee('data-testid="notification-badge"', false)
            ->assertSee('Demo notification visible')
            ->assertSee('View all notifications');
    }

    public function test_generic_notifications_route_redirects_by_role(): void
    {
        $student = User::create([
            'name' => 'Notification Student',
            'email' => 'notification-redirect@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->actingAs($student)
            ->get(route('notifications.redirect'))
            ->assertRedirect(route('student.notifications'));
    }
}
