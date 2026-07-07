<?php

namespace App\Filament\Resources\Lessons;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Rules\YouTubeUrl;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class LessonResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = Lesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Lessons';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_id')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Select::make('lesson_type')
                    ->required()
                    ->options([
                        'video' => 'Video',
                        'text' => 'Text',
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'live' => 'Live',
                    ])
                    ->default('text'),
                TextInput::make('video_url')
                    ->label('YouTube video URL')
                    ->placeholder('https://www.youtube.com/watch?v=VIDEO_ID')
                    ->helperText('Examples: https://www.youtube.com/watch?v=VIDEO_ID, https://youtu.be/VIDEO_ID, https://www.youtube.com/shorts/VIDEO_ID, or https://www.youtube.com/embed/VIDEO_ID. Do not paste iframe HTML.')
                    ->maxLength(255)
                    ->rules([
                        'nullable',
                        'url',
                        new YouTubeUrl(),
                    ]),
                Textarea::make('summary')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                MarkdownEditor::make('content')
                    ->label('Content / Body')
                    ->columnSpanFull(),
                TextInput::make('duration_minutes')
                    ->numeric()
                    ->label('Duration minutes'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_free_preview')
                    ->label('Free preview'),
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
                TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lesson_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Minutes')
                    ->sortable(),
                IconColumn::make('is_free_preview')
                    ->boolean()
                    ->label('Preview'),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('lesson_type')
                    ->options([
                        'video' => 'Video',
                        'text' => 'Text',
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'live' => 'Live',
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
            'index' => ListLessons::route('/'),
            'create' => CreateLesson::route('/create'),
            'edit' => EditLesson::route('/{record}/edit'),
        ];
    }
}
