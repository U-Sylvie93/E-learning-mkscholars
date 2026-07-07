<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['role'] ?? null) === User::ROLE_ADMIN) {
            $data['approval_status'] = User::APPROVAL_APPROVED;
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        if (($data['role'] ?? null) === User::ROLE_VIEWER) {
            $data['approval_status'] = User::APPROVAL_APPROVED;
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        return $data;
    }
}
