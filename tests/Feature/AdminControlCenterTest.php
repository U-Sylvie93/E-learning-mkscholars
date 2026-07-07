<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminControlCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_account_settings_page_updates_profile_and_password(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'settings-admin@example.test');

        $this->actingAs($admin)
            ->get('/admin/account-settings')
            ->assertOk()
            ->assertSee('Account Settings')
            ->assertSee('Change Password')
            ->assertDontSee('name="role"', false);

        $this->actingAs($admin)
            ->post(route('admin.account-settings.profile'), [
                'name' => 'Updated Admin',
                'email' => 'updated-admin@example.test',
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('updated-admin@example.test', $admin->email);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);

        $this->actingAs($admin)
            ->post(route('admin.account-settings.password'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $admin->refresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'wrong-password-admin@example.test');

        $this->actingAs($admin)
            ->from('/admin/account-settings')
            ->post(route('admin.account-settings.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/admin/account-settings')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_viewer_can_access_admin_but_cannot_mutate_key_resources(): void
    {
        $viewer = $this->user(User::ROLE_VIEWER, 'viewer@example.test');
        $student = $this->user(User::ROLE_STUDENT, 'viewer-student@example.test');
        $course = Course::factory()->create(['status' => Course::STATUS_PUBLISHED]);
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        $this->actingAs($viewer);

        $this->assertTrue($viewer->isReadOnlyAdminViewer());
        $this->assertFalse(\App\Filament\Resources\Users\UserResource::canCreate());
        $this->assertFalse(\App\Filament\Resources\Certificates\CertificateResource::canEdit($certificate));

        $this->post(route('admin.account-settings.profile'), [
                'name' => 'Viewer Changed',
                'email' => 'viewer-changed@example.test',
            ])
            ->assertForbidden();

        $viewer->refresh();
        $this->assertSame('viewer@example.test', $viewer->email);
    }

    public function test_subscription_admin_list_contains_subscriber_context(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'subscription-admin@example.test');
        $student = $this->user(User::ROLE_STUDENT, 'subscriber@example.test');
        $plan = SubscriptionPlan::create([
            'name' => 'Premium Access',
            'slug' => 'premium-access',
            'description' => 'Premium plan',
            'price_amount' => 5000,
            'currency' => 'RWF',
            'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
            'duration_days' => 30,
            'status' => SubscriptionPlan::STATUS_ACTIVE,
            'features' => [],
        ]);
        $payment = Payment::create([
            'user_id' => $student->id,
            'amount' => 5000,
            'currency' => 'RWF',
            'purpose' => Payment::PURPOSE_SUBSCRIPTION,
            'status' => Payment::STATUS_APPROVED,
        ]);
        Subscription::create([
            'user_id' => $student->id,
            'subscription_plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('subscriber@example.test')
            ->assertSee('Premium Access')
            ->assertSee('approved');
    }

    public function test_admin_panel_exposes_view_website_link(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'home-link-admin@example.test');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('View Website');
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
