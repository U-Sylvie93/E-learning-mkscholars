<?php

namespace App\Filament\Resources\LiveClassAttendances\Pages;

use App\Filament\Resources\LiveClassAttendances\LiveClassAttendanceResource;
use Filament\Resources\Pages\ListRecords;

class ListLiveClassAttendances extends ListRecords
{
    protected static string $resource = LiveClassAttendanceResource::class;
}
