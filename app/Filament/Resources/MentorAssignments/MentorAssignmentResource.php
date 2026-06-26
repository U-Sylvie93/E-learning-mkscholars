<?php

namespace App\Filament\Resources\MentorAssignments;

use App\Filament\Resources\MentorAssignments\Pages\CreateMentorAssignment;
use App\Filament\Resources\MentorAssignments\Pages\EditMentorAssignment;
use App\Filament\Resources\MentorAssignments\Pages\ListMentorAssignments;
use App\Models\MentorAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MentorAssignmentResource extends Resource
{
    protected static ?string $model = MentorAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Mentor Assignments';

    protected static string|UnitEnum|null $navigationGroup = 'Mentorship';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mentor_id')
                    ->label('Mentor')
                    ->options(fn () => User::query()
                        ->where('role', User::ROLE_MENTOR)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->required()
                    ->options([
                        MentorAssignment::STATUS_ACTIVE => 'Active',
                        MentorAssignment::STATUS_COMPLETED => 'Completed',
                        MentorAssignment::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->default(MentorAssignment::STATUS_ACTIVE),
                DateTimePicker::make('assigned_at')
                    ->required()
                    ->default(now()),
                DateTimePicker::make('ended_at'),
                Textarea::make('notes')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->placeholder('General mentorship'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_ins_count')
                    ->counts('checkIns')
                    ->label('Check-ins')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('mentor')
                    ->relationship('mentor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        MentorAssignment::STATUS_ACTIVE => 'Active',
                        MentorAssignment::STATUS_COMPLETED => 'Completed',
                        MentorAssignment::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMentorAssignments::route('/'),
            'create' => CreateMentorAssignment::route('/create'),
            'edit' => EditMentorAssignment::route('/{record}/edit'),
        ];
    }
}
