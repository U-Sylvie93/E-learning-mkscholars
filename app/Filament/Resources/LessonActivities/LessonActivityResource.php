<?php

namespace App\Filament\Resources\LessonActivities;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\LessonActivities\Pages\CreateLessonActivity;
use App\Filament\Resources\LessonActivities\Pages\EditLessonActivity;
use App\Filament\Resources\LessonActivities\Pages\ListLessonActivities;
use App\Models\Course;
use App\Models\LessonActivity;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class LessonActivityResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = LessonActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Lesson Activities';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('activity_type')
                    ->required()
                    ->options([
                        'video' => 'Video',
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'download' => 'Download',
                        'discussion' => 'Discussion',
                    ])
                    ->default('video'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('instructions')
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('resource_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->default(Course::STATUS_DRAFT),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lesson.title')
                    ->label('Lesson')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lesson')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('activity_type')
                    ->options([
                        'video' => 'Video',
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'download' => 'Download',
                        'discussion' => 'Discussion',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
                    ]),
            ])
            ->defaultSort('sort_order')
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
            'index' => ListLessonActivities::route('/'),
            'create' => CreateLessonActivity::route('/create'),
            'edit' => EditLessonActivity::route('/{record}/edit'),
        ];
    }
}
