<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_service_expires_an_old_code_and_generates_a_new_one(): void
    {
        Mail::fake();

        $user = $this->user('reset-service@mkscholars.test');
        $service = app(PasswordResetService::class);

        $oldReset = $service->createFor($user, '127.0.0.1');
        $newReset = $service->createFor($user, '127.0.0.1');

        $this->assertSame(PasswordResetRequest::STATUS_EXPIRED, $oldReset->fresh()->status);
        $this->assertSame(PasswordResetRequest::STATUS_PENDING, $newReset->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $newReset->otp);
        $this->assertTrue($newReset->expires_at->isFuture());
    }

    public function test_public_forgot_password_uses_the_shared_reset_service(): void
    {
        Mail::fake();

        $user = $this->user('reset-user@mkscholars.test');

        $this->post(route('password.forgot.store'), ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['email' => $user->email]));

        $this->assertDatabaseHas('password_reset_requests', [
            'email' => $user->email,
            'user_id' => $user->id,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Reset User',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
