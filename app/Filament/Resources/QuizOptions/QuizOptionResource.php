<?php

namespace App\Filament\Resources\QuizOptions;

use App\Filament\Resources\QuizOptions\Pages\CreateQuizOption;
use App\Filament\Resources\QuizOptions\Pages\EditQuizOption;
use App\Filament\Resources\QuizOptions\Pages\ListQuizOptions;
use App\Models\QuizOption;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class QuizOptionResource extends Resource
{
    protected static ?string $model = QuizOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $recordTitleAttribute = 'option_text';

    protected static ?string $navigationLabel = 'Quiz Options';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quiz_question_id')
                    ->label('Quiz question')
                    ->helperText('Attach this answer option to the correct question.')
                    ->relationship('question', 'question_text')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('option_text')
                    ->label('Answer option text')
                    ->placeholder('Example: True, False, or a full multiple-choice option.')
                    ->helperText('This is what students can select.')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_correct')
                    ->label('Mark as correct answer')
                    ->helperText('Turn on for the option that should receive points.'),
                TextInput::make('sort_order')
                    ->label('Display order')
                    ->helperText('Lower numbers appear first under the question.')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('option_text')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('question.question_text')
                    ->label('Question')
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('is_correct')
                    ->boolean()
                    ->label('Correct'),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_correct')
                    ->label('Correct answer'),
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
            'index' => ListQuizOptions::route('/'),
            'create' => CreateQuizOption::route('/create'),
            'edit' => EditQuizOption::route('/{record}/edit'),
        ];
    }
}


