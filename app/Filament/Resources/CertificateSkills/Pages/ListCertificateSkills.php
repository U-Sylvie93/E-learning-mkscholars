<?php

namespace App\Filament\Resources\CertificateSkills\Pages;

use App\Filament\Resources\CertificateSkills\CertificateSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCertificateSkills extends ListRecords
{
    protected static string $resource = CertificateSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
