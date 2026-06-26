<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailNotificationService
{
    public function sendToUser(User|int|null $user, Notification $notification): bool
    {
        if (! (bool) config('mkscholars.email_notifications.enabled')) {
            return false;
        }

        $user = $user instanceof User ? $user : User::find($user);

        if (! $user || blank($user->email)) {
            return false;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('MK Scholars email notification failed.', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
