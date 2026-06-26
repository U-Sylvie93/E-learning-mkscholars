<?php

namespace App\Filament\Resources\ApplicationDocuments\Pages;

use App\Filament\Resources\ApplicationDocuments\ApplicationDocumentResource;
use Filament\Resources\Pages\EditRecord;

class EditApplicationDocument extends EditRecord
{
    protected static string $resource = ApplicationDocumentResource::class;
}
