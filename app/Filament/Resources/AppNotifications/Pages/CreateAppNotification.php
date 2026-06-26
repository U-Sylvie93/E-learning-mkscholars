<?php

namespace App\Filament\Resources\AppNotifications\Pages;

use App\Filament\Resources\AppNotifications\AppNotificationResource;
use App\Services\AppNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateAppNotification extends CreateRecord
{
    protected static string $resource = AppNotificationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (blank($data['user_id'] ?? null) && blank($data['role'] ?? null)) {
            throw ValidationException::withMessages([
                'user_id' => 'Choose a target user or target role.',
            ]);
        }

        if (filled($data['role'] ?? null)) {
            $notifications = app(AppNotificationService::class)->createForRole($data['role'], $data);

            return $notifications->first() ?? parent::handleRecordCreation($data);
        }

        return app(AppNotificationService::class)->createForUser((int) $data['user_id'], $data);
    }
}
