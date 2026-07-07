<?php

namespace App\Filament\Resources\CourseCompletionRules;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\CourseCompletionRules\Pages\CreateCourseCompletionRule;
use App\Filament\Resources\CourseCompletionRules\Pages\EditCourseCompletionRule;
use App\Filament\Resources\CourseCompletionRules\Pages\ListCourseCompletionRules;
use App\Models\CourseCompletionRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CourseCompletionRuleResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = CourseCompletionRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Completion Rules';

    protected static string|UnitEnum|null $navigationGroup = 'Credentials';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('course_id')
                ->relationship('course', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('required_lesson_percentage')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(80)
                ->required(),
            Toggle::make('require_all_lessons')->default(false),
            TextInput::make('required_quiz_percentage')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(50)
                ->required(),
            Toggle::make('require_all_published_quizzes_passed')->default(false),
            Toggle::make('require_all_published_assignments_submitted')->default(false),
            Toggle::make('require_final_quiz_passed')->default(false),
            Select::make('final_quiz_id')
                ->relationship('finalQuiz', 'title')
                ->searchable()
                ->preload(),
            TextInput::make('required_live_class_attendance_percentage')
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
            Select::make('status')
                ->required()
                ->options([
                    CourseCompletionRule::STATUS_ACTIVE => 'Active',
                    CourseCompletionRule::STATUS_INACTIVE => 'Inactive',
                ])
                ->default(CourseCompletionRule::STATUS_ACTIVE),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('Course')->searchable()->sortable(),
                TextColumn::make('required_lesson_percentage')->suffix('%')->sortable(),
                IconColumn::make('require_all_lessons')->boolean(),
                TextColumn::make('required_quiz_percentage')->suffix('%')->sortable(),
                IconColumn::make('require_all_published_quizzes_passed')->boolean(),
                IconColumn::make('require_all_published_assignments_submitted')->boolean(),
                IconColumn::make('require_final_quiz_passed')->boolean(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    CourseCompletionRule::STATUS_ACTIVE => 'Active',
                    CourseCompletionRule::STATUS_INACTIVE => 'Inactive',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseCompletionRules::route('/'),
            'create' => CreateCourseCompletionRule::route('/create'),
            'edit' => EditCourseCompletionRule::route('/{record}/edit'),
        ];
    }
}
