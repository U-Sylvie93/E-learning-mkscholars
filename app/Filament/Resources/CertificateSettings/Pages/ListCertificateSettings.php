<?php

namespace App\Filament\Resources\CertificateSettings\Pages;

use App\Filament\Resources\CertificateSettings\CertificateSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCertificateSettings extends ListRecords
{
    protected static string $resource = CertificateSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->visible(CertificateSettingResource::canCreate())];
    }
}
