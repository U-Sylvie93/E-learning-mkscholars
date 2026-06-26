<?php

namespace App\Filament\Resources\CertificateSkills\Pages;

use App\Filament\Resources\CertificateSkills\CertificateSkillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCertificateSkill extends EditRecord
{
    protected static string $resource = CertificateSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
