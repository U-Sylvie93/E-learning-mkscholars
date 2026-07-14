<?php

namespace App\Filament\Resources\QuizQuestions;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\QuizQuestions\Pages\CreateQuizQuestion;
use App\Filament\Resources\QuizQuestions\Pages\EditQuizQuestion;
use App\Filament\Resources\QuizQuestions\Pages\ListQuizQuestions;
use App\Models\QuizQuestion;
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

class QuizQuestionResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = QuizQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $recordTitleAttribute = 'question_text';

    protected static ?string $navigationLabel = 'Quiz Questions';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quiz_id')
                    ->label('Parent quiz')
                    ->helperText('Choose the quiz this question belongs to.')
                    ->relationship('quiz', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('question_text')
                    ->label('Question text')
                    ->placeholder('Write the question exactly as students should see it.')
                    ->helperText('Add answer options separately only for single-choice, multiple-choice, and true/false questions.')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Select::make('question_type')
                    ->label('Question type')
                    ->helperText('Option-based questions are automatically graded. Text answers are saved for review and are not auto-scored.')
                    ->required()
                    ->options([
                        QuizQuestion::TYPE_SINGLE_CHOICE => 'Single choice',
                        QuizQuestion::TYPE_MULTIPLE_CHOICE => 'Multiple choice',
                        QuizQuestion::TYPE_TRUE_FALSE => 'True / false',
                        QuizQuestion::TYPE_SHORT_ANSWER => 'Short answer',
                        QuizQuestion::TYPE_LONG_ANSWER => 'Long answer',
                        QuizQuestion::TYPE_TEXT => 'Text',
                        QuizQuestion::TYPE_ESSAY => 'Essay',
                    ])
                    ->default(QuizQuestion::TYPE_MULTIPLE_CHOICE),
                TextInput::make('points')
                    ->label('Points')
                    ->helperText('Awarded only when the selected option is marked correct.')
                    ->numeric()
                    ->minValue(0)
                    ->default(1)
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->helperText('Lower numbers appear first on the student quiz page.')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->label('Question status')
                    ->helperText('Only published questions appear on student quizzes.')
                    ->required()
                    ->options([
                        QuizQuestion::STATUS_DRAFT => 'Draft',
                        QuizQuestion::STATUS_PUBLISHED => 'Published',
                    ])
                    ->default(QuizQuestion::STATUS_DRAFT),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_text')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('quiz.title')
                    ->label('Quiz')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('question_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('points')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Options')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('quiz')
                    ->relationship('quiz', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('question_type')
                    ->options([
                        QuizQuestion::TYPE_SINGLE_CHOICE => 'Single choice',
                        QuizQuestion::TYPE_MULTIPLE_CHOICE => 'Multiple choice',
                        QuizQuestion::TYPE_TRUE_FALSE => 'True / false',
                        QuizQuestion::TYPE_SHORT_ANSWER => 'Short answer',
                        QuizQuestion::TYPE_LONG_ANSWER => 'Long answer',
                        QuizQuestion::TYPE_TEXT => 'Text',
                        QuizQuestion::TYPE_ESSAY => 'Essay',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        QuizQuestion::STATUS_DRAFT => 'Draft',
                        QuizQuestion::STATUS_PUBLISHED => 'Published',
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
            'index' => ListQuizQuestions::route('/'),
            'create' => CreateQuizQuestion::route('/create'),
            'edit' => EditQuizQuestion::route('/{record}/edit'),
        ];
    }
}

