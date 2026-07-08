<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->id === auth()->id()) {
            $data['role'] = $this->record->role;
            $data['approval_status'] = $this->record->approval_status;
            $data['approved_at'] = $this->record->approved_at;
            $data['approved_by'] = $this->record->approved_by;
        }

        if ($this->record->role === User::ROLE_ADMIN) {
            $data['approval_status'] = User::APPROVAL_APPROVED;
            $data['approved_at'] = $this->record->approved_at ?? now();
            $data['approved_by'] = $this->record->approved_by;
        }

        if (($data['role'] ?? null) === User::ROLE_ADMIN && $this->record->role !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'Use first-admin setup or direct trusted database maintenance for admin promotion.',
            ]);
        }

        if (($data['approval_status'] ?? null) === User::APPROVAL_APPROVED && $this->record->approval_status !== User::APPROVAL_APPROVED) {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        if (in_array($data['approval_status'] ?? null, [User::APPROVAL_PENDING, User::APPROVAL_REJECTED, User::APPROVAL_SUSPENDED], true)) {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
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

        if (in_array($data['role'] ?? null, [User::ROLE_VIEWER, User::ROLE_CONTENT_EDITOR], true)
            && ($data['approval_status'] ?? null) === User::APPROVAL_APPROVED
            && empty($data['approved_at'])) {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        return $data;
    }
}
