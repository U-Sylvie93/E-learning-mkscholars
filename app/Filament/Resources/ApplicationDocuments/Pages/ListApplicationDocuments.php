<?php

namespace App\Filament\Resources\ApplicationDocuments\Pages;

use App\Filament\Resources\ApplicationDocuments\ApplicationDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListApplicationDocuments extends ListRecords
{
    protected static string $resource = ApplicationDocumentResource::class;
}
