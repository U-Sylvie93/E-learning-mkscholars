<?php

namespace App\Filament\Resources\ContactSubmissions\Pages;

use App\Filament\Resources\ContactSubmissions\ContactSubmissionResource;
use App\Models\ContactSubmission;
use Filament\Resources\Pages\EditRecord;

class EditContactSubmission extends EditRecord
{
    protected static string $resource = ContactSubmissionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['status'] ?? null) === ContactSubmission::STATUS_NEW) {
            $this->record->update([
                'status' => ContactSubmission::STATUS_READ,
                'read_at' => now(),
            ]);
            $data['status'] = ContactSubmission::STATUS_READ;
        }

        return $data;
    }
}
