<?php

namespace App\Filament\Resources\CertificateSettings\Pages;

use App\Filament\Resources\CertificateSettings\CertificateSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditCertificateSetting extends EditRecord
{
    protected static string $resource = CertificateSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
