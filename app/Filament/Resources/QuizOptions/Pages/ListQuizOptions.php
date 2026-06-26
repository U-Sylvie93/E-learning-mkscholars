<?php

namespace App\Filament\Resources\QuizOptions\Pages;

use App\Filament\Resources\QuizOptions\QuizOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizOptions extends ListRecords
{
    protected static string $resource = QuizOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
