<?php

namespace App\Filament\Resources\QuizOptions\Pages;

use App\Filament\Resources\QuizOptions\QuizOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuizOption extends EditRecord
{
    protected static string $resource = QuizOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
