<?php

namespace App\Filament\Resources\LiveClasses\Pages;

use App\Filament\Resources\LiveClasses\LiveClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiveClasses extends ListRecords
{
    protected static string $resource = LiveClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
