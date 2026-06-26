<?php

namespace App\Filament\Resources\Assignments;

use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Filament\Resources\Assignments\Pages\EditAssignment;
use App\Filament\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
use UnitEnum;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Assignments';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('instructions')
                    ->label('Instructions')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                FileUpload::make('instruction_file_path')
                    ->label('Assignment document')
                    ->helperText('Optional supporting file for students. Allowed: PDF, DOC, DOCX, TXT, ZIP, PNG, JPG, JPEG. Max 10MB.')
                    ->disk('public')
                    ->directory('assignment-instructions')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain',
                        'application/zip',
                        'image/png',
                        'image/jpeg',
                    ])
                    ->maxSize(10240)
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
                Select::make('submission_type')
                    ->required()
                    ->options([
                        Assignment::TYPE_TEXT => 'Text',
                        Assignment::TYPE_FILE => 'File',
                        Assignment::TYPE_LINK => 'Link',
                        Assignment::TYPE_MIXED => 'Mixed',
                    ])
                    ->default(Assignment::TYPE_TEXT),
                TextInput::make('max_score')
                    ->numeric()
                    ->minValue(1)
                    ->default(100)
                    ->required(),
                TextInput::make('due_days_after_enrollment')
                    ->numeric()
                    ->minValue(1),
                Toggle::make('allow_late_submission')
                    ->default(true),
                Select::make('status')
                    ->required()
                    ->options([
                        Assignment::STATUS_DRAFT => 'Draft',
                        Assignment::STATUS_PUBLISHED => 'Published',
                        Assignment::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->default(Assignment::STATUS_DRAFT),
                Repeater::make('questions')
                    ->relationship('questions')
                    ->label('Assignment questions')
                    ->helperText('Add simple written questions students answer when submitting this assignment.')
                    ->schema([
                        Textarea::make('question_text')
                            ->label('Question')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('question_type')
                            ->label('Answer type')
                            ->required()
                            ->options([
                                AssignmentQuestion::TYPE_TEXT => 'Short text',
                                AssignmentQuestion::TYPE_TEXTAREA => 'Long answer',
                            ])
                            ->default(AssignmentQuestion::TYPE_TEXTAREA),
                        TextInput::make('points')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->columnSpanFull(),
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
                TextColumn::make('submission_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('max_score')
                    ->sortable(),
                IconColumn::make('allow_late_submission')
                    ->boolean()
                    ->label('Late'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),
                TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Submissions')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lesson')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('submission_type')
                    ->options([
                        Assignment::TYPE_TEXT => 'Text',
                        Assignment::TYPE_FILE => 'File',
                        Assignment::TYPE_LINK => 'Link',
                        Assignment::TYPE_MIXED => 'Mixed',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Assignment::STATUS_DRAFT => 'Draft',
                        Assignment::STATUS_PUBLISHED => 'Published',
                        Assignment::STATUS_ARCHIVED => 'Archived',
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
            'index' => ListAssignments::route('/'),
            'create' => CreateAssignment::route('/create'),
            'edit' => EditAssignment::route('/{record}/edit'),
        ];
    }
}
