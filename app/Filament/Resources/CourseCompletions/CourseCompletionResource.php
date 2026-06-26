<?php

namespace App\Filament\Resources\CourseCompletions;

use App\Filament\Resources\CourseCompletions\Pages\ListCourseCompletions;
use App\Models\CourseCompletion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CourseCompletionResource extends Resource
{
    protected static ?string $model = CourseCompletion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Course Completions';

    protected static string|UnitEnum|null $navigationGroup = 'Credentials';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Student')->searchable()->sortable(),
                TextColumn::make('course.title')->label('Course')->searchable()->sortable(),
                TextColumn::make('lesson_percentage')->suffix('%')->sortable(),
                TextColumn::make('quiz_percentage')->suffix('%')->sortable(),
                TextColumn::make('assignment_percentage')->suffix('%')->sortable(),
                TextColumn::make('live_attendance_percentage')->suffix('%')->placeholder('Not required')->sortable(),
                IconColumn::make('is_eligible_for_certificate')->label('Eligible')->boolean()->sortable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('Not complete')->sortable(),
                TextColumn::make('last_checked_at')->dateTime()->placeholder('Not checked')->sortable(),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_eligible_for_certificate')
                    ->label('Certificate eligible')
                    ->options([
                        1 => 'Eligible',
                        0 => 'Not eligible',
                    ]),
            ])
            ->defaultSort('last_checked_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseCompletions::route('/'),
        ];
    }
}
