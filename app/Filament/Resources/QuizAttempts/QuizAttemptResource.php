<?php

namespace App\Filament\Resources\QuizAttempts;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class QuizAttemptResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Quiz Attempts';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quiz.title')
                    ->label('Quiz')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('score')
                    ->sortable(),
                TextColumn::make('total_points')
                    ->label('Total')
                    ->sortable(),
                TextColumn::make('percentage')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('quiz')
                    ->relationship('quiz', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        QuizAttempt::STATUS_IN_PROGRESS => 'In progress',
                        QuizAttempt::STATUS_SUBMITTED => 'Submitted',
                        QuizAttempt::STATUS_PASSED => 'Passed',
                        QuizAttempt::STATUS_FAILED => 'Failed',
                    ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
        ];
    }
}
