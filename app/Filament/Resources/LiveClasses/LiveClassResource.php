<?php

namespace App\Filament\Resources\LiveClasses;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\LiveClasses\Pages\CreateLiveClass;
use App\Filament\Resources\LiveClasses\Pages\EditLiveClass;
use App\Filament\Resources\LiveClasses\Pages\ListLiveClasses;
use App\Models\LiveClass;
use App\Models\User;
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

class LiveClassResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = LiveClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Live Classes';

    protected static string|UnitEnum|null $navigationGroup = 'Live Learning';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('module_id')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('instructor_id')
                    ->options(fn () => User::query()
                        ->where('role', User::ROLE_INSTRUCTOR)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->label('Instructor'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('meeting_url')
                    ->url()
                    ->required()
                    ->maxLength(255),
                Select::make('platform')
                    ->required()
                    ->options([
                        LiveClass::PLATFORM_ZOOM => 'Zoom',
                        LiveClass::PLATFORM_GOOGLE_MEET => 'Google Meet',
                        LiveClass::PLATFORM_TEAMS => 'Microsoft Teams',
                        LiveClass::PLATFORM_OTHER => 'Other',
                    ])
                    ->default(LiveClass::PLATFORM_ZOOM),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options([
                        LiveClass::STATUS_SCHEDULED => 'Scheduled',
                        LiveClass::STATUS_LIVE => 'Live',
                        LiveClass::STATUS_COMPLETED => 'Completed',
                        LiveClass::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->default(LiveClass::STATUS_SCHEDULED),
                TextInput::make('recording_url')
                    ->url()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Linked via module/lesson'),
                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                TextColumn::make('platform')
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Attendance')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('platform')
                    ->options([
                        LiveClass::PLATFORM_ZOOM => 'Zoom',
                        LiveClass::PLATFORM_GOOGLE_MEET => 'Google Meet',
                        LiveClass::PLATFORM_TEAMS => 'Microsoft Teams',
                        LiveClass::PLATFORM_OTHER => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        LiveClass::STATUS_SCHEDULED => 'Scheduled',
                        LiveClass::STATUS_LIVE => 'Live',
                        LiveClass::STATUS_COMPLETED => 'Completed',
                        LiveClass::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->defaultSort('starts_at')
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
            'index' => ListLiveClasses::route('/'),
            'create' => CreateLiveClass::route('/create'),
            'edit' => EditLiveClass::route('/{record}/edit'),
        ];
    }
}
