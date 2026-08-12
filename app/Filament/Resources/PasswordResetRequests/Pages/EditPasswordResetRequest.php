<?php

namespace App\Filament\Resources\PasswordResetRequests\Pages;

use App\Filament\Resources\PasswordResetRequests\PasswordResetRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditPasswordResetRequest extends EditRecord
{
    protected static string $resource = PasswordResetRequestResource::class;
}
