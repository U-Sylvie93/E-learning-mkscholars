<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginOneClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_one_clean_post_form(): void
    {
        $response = $this->get(route('login'))->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<form '));
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('action="/login"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="remember"', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringNotContainsString('<form <form', $html);
        $this->assertStringNotContainsString('action="http://', $html);
    }

    public function test_student_instructor_and_admin_can_login_with_one_post_request(): void
    {
        $cases = [
            [User::ROLE_STUDENT, 'student-one-click@mkscholars.test', '/student/dashboard'],
            [User::ROLE_INSTRUCTOR, 'instructor-one-click@mkscholars.test', '/instructor/dashboard'],
            [User::ROLE_ADMIN, 'admin-one-click@mkscholars.test', '/admin'],
        ];

        foreach ($cases as [$role, $email, $path]) {
            $user = $this->approvedUser($role, $email);

            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'password',
            ])->assertRedirect($path);

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_invalid_password_returns_validation_error(): void
    {
        $this->approvedUser(User::ROLE_STUDENT, 'bad-password-one-click@mkscholars.test');

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'bad-password-one-click@mkscholars.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_pending_instructor_remains_blocked(): void
    {
        User::create([
            'name' => 'Pending Instructor',
            'email' => 'pending-one-click@mkscholars.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUCTOR,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'pending-one-click@mkscholars.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_filament_admin_login_route_remains_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    private function approvedUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst(str_replace('_', ' ', $role)).' User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
