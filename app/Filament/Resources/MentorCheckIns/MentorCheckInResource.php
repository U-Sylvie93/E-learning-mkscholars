<?php

namespace App\Filament\Resources\MentorCheckIns;

use App\Filament\Resources\MentorCheckIns\Pages\CreateMentorCheckIn;
use App\Filament\Resources\MentorCheckIns\Pages\EditMentorCheckIn;
use App\Filament\Resources\MentorCheckIns\Pages\ListMentorCheckIns;
use App\Models\MentorCheckIn;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MentorCheckInResource extends Resource
{
    protected static ?string $model = MentorCheckIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Mentor Check-ins';

    protected static string|UnitEnum|null $navigationGroup = 'Mentorship';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('mkscholars.features.mentorship_enabled', false);
    }

    public static function canViewAny(): bool
    {
        return (bool) config('mkscholars.features.mentorship_enabled', false);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mentor_assignment_id')
                    ->relationship('mentorAssignment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => ($record->mentor?->name ?? 'Mentor').' / '.($record->student?->name ?? 'Student'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('topic')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('completed_at'),
                Textarea::make('student_notes')
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('mentor_feedback')
                    ->rows(5)
                    ->columnSpanFull(),
                Select::make('status')
                    ->required()
                    ->options([
                        MentorCheckIn::STATUS_SCHEDULED => 'Scheduled',
                        MentorCheckIn::STATUS_COMPLETED => 'Completed',
                        MentorCheckIn::STATUS_MISSED => 'Missed',
                        MentorCheckIn::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->default(MentorCheckIn::STATUS_SCHEDULED),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mentorAssignment.mentor.name')
                    ->label('Mentor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mentorAssignment.student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        MentorCheckIn::STATUS_SCHEDULED => 'Scheduled',
                        MentorCheckIn::STATUS_COMPLETED => 'Completed',
                        MentorCheckIn::STATUS_MISSED => 'Missed',
                        MentorCheckIn::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->defaultSort('scheduled_at')
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
            'index' => ListMentorCheckIns::route('/'),
            'create' => CreateMentorCheckIn::route('/create'),
            'edit' => EditMentorCheckIn::route('/{record}/edit'),
        ];
    }
}

