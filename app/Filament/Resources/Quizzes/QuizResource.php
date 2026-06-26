<?php

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Pages\ListQuizzes;
use App\Models\Quiz;
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

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Quizzes';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lesson_id')
                    ->label('Lesson for this quiz')
                    ->helperText('Choose the lesson where students will see this quiz.')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label('Quiz title')
                    ->placeholder('Example: Module 1 Knowledge Check')
                    ->helperText('Use a clear student-facing title.')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Instructions / description')
                    ->helperText('Optional instructions shown before students answer the quiz.')
                    ->placeholder('Tell students what this quiz covers and how to prepare.')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('passing_score')
                    ->label('Passing score (%)')
                    ->helperText('Students pass when their calculated percentage meets or exceeds this value.')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(50)
                    ->required(),
                TextInput::make('max_attempts')
                    ->label('Maximum attempts')
                    ->helperText('Leave blank for unlimited attempts.')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('time_limit_minutes')
                    ->label('Time limit (minutes)')
                    ->helperText('Shown to students for guidance. No countdown is enforced in this phase.')
                    ->numeric()
                    ->minValue(1),
                Select::make('status')
                    ->label('Publication status')
                    ->helperText('Only published quizzes are visible to students.')
                    ->required()
                    ->options([
                        Quiz::STATUS_DRAFT => 'Draft',
                        Quiz::STATUS_PUBLISHED => 'Published',
                        Quiz::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->default(Quiz::STATUS_DRAFT),
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
                TextColumn::make('passing_score')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('max_attempts')
                    ->placeholder('Unlimited')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lesson')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        Quiz::STATUS_DRAFT => 'Draft',
                        Quiz::STATUS_PUBLISHED => 'Published',
                        Quiz::STATUS_ARCHIVED => 'Archived',
                    ]),
            ])
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
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}


