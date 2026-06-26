<?php

namespace App\Filament\Resources\StudentApplications\Pages;

use App\Filament\Resources\StudentApplications\StudentApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentApplications extends ListRecords
{
    protected static string $resource = StudentApplicationResource::class;
}
