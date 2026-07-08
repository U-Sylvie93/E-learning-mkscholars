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
        if (in_array($data['role'] ?? null, [User::ROLE_ADMIN, User::ROLE_VIEWER, User::ROLE_CONTENT_EDITOR], true)) {
            $data['approval_status'] = User::APPROVAL_APPROVED;
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        return $this->normalizeAccessFields($data);
    }

    private function normalizeAccessFields(array $data): array
    {
        if (($data['role'] ?? null) !== User::ROLE_VIEWER) {
            $data['viewer_permissions'] = null;
        }

        if (($data['role'] ?? null) !== User::ROLE_CONTENT_EDITOR) {
            $data['content_permissions'] = null;
            $data['content_course_ids'] = null;
        }

        return $data;
    }
}
