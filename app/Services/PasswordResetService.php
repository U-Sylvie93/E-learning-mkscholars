<?php

namespace App\Services;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PasswordResetService
{
    public const MAX_REQUESTS_PER_HOUR = 5;

    public function hasReachedRateLimit(User $user): bool
    {
        return PasswordResetRequest::query()
            ->where('email', $user->email)
            ->where('created_at', '>=', now()->subHour())
            ->count() >= self::MAX_REQUESTS_PER_HOUR;
    }

    public function createFor(User $user, ?string $requestIp = null): PasswordResetRequest
    {
        PasswordResetRequest::query()
            ->where('email', $user->email)
            ->where('status', PasswordResetRequest::STATUS_PENDING)
            ->update(['status' => PasswordResetRequest::STATUS_EXPIRED]);

        $reset = PasswordResetRequest::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'otp' => PasswordResetRequest::generateOtp(),
            'status' => PasswordResetRequest::STATUS_PENDING,
            'expires_at' => now()->addMinutes(30),
            'request_ip' => $requestIp,
        ]);

        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour MK Scholars password reset code is: {$reset->otp}\n\nIt expires in 30 minutes. If you did not request this, you can ignore this message.\n\n- MK Scholars",
                function ($message) use ($user): void {
                    $message->to($user->email)
                        ->subject('Your MK Scholars password reset code');
                }
            );
        } catch (Throwable $exception) {
            Log::warning('Password reset mail failed: '.$exception->getMessage(), [
                'email' => $user->email,
                'reset_id' => $reset->id,
            ]);
        }

        return $reset;
    }
}
