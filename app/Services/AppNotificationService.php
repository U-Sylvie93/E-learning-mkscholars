<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AppNotificationService
{
    public function createForUser(User|int $user, array $data): AppNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->createNotification([
            ...$data,
            'user_id' => $userId,
            'role' => $data['role'] ?? null,
        ]);
    }

    public function createForRole(string $role, array $data): Collection
    {
        $notifications = new Collection();

        User::query()
            ->where('role', $role)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($role, $data, $notifications): void {
                foreach ($users as $user) {
                    $notifications->push($this->createNotification([
                        ...$data,
                        'user_id' => $user->id,
                        'role' => $role,
                    ]));
                }
            });

        return $notifications;
    }

    public function visibleFor(User $user)
    {
        return AppNotification::query()
            ->where('user_id', $user->id)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->latest();
    }

    public function unreadCount(User $user): int
    {
        return (clone $this->visibleFor($user))
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(AppNotification $notification, User $user): void
    {
        abort_unless($notification->user_id === $user->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(User $user): int
    {
        return (clone $this->visibleFor($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function createNotification(array $data): AppNotification
    {
        $payload = [
            'user_id' => $data['user_id'] ?? null,
            'role' => $data['role'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? AppNotification::TYPE_INFO,
            'category' => $data['category'] ?? AppNotification::CATEGORY_SYSTEM,
            'action_url' => $data['action_url'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ];

        $existing = AppNotification::query()
            ->where('user_id', $payload['user_id'])
            ->where('title', $payload['title'])
            ->where('category', $payload['category'])
            ->where('action_url', $payload['action_url'])
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            return $existing;
        }

        return AppNotification::create($payload);
    }
}
