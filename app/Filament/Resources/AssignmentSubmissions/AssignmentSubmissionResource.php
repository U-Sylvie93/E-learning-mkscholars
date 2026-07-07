<?php

namespace App\Filament\Resources\AssignmentSubmissions;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\AssignmentSubmissions\Pages\EditAssignmentSubmission;
use App\Filament\Resources\AssignmentSubmissions\Pages\ListAssignmentSubmissions;
use App\Models\AssignmentSubmission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class AssignmentSubmissionResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = AssignmentSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'Assignment Submissions';

    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission review')
                    ->description('Review the submitted answers, file, and external link before grading.')
                    ->schema([
                        Placeholder::make('review_panel')
                            ->hiddenLabel()
                            ->content(fn (?AssignmentSubmission $record): HtmlString => new HtmlString(
                                view('partials.assignment-submission-review', ['submission' => $record])->render()
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Grading panel')
                    ->description('Record the grade and feedback visible to the student.')
                    ->schema([
                        TextInput::make('score')
                            ->numeric()
                            ->minValue(0),
                        Select::make('status')
                            ->required()
                            ->options([
                                AssignmentSubmission::STATUS_SUBMITTED => 'Submitted',
                                AssignmentSubmission::STATUS_GRADED => 'Graded',
                                AssignmentSubmission::STATUS_RESUBMISSION_REQUIRED => 'Resubmission required',
                            ]),
                        Textarea::make('feedback')
                            ->rows(6)
                            ->placeholder('Share clear next steps, corrections, or praise for the student.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('score')
                    ->sortable()
                    ->placeholder('Not graded'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('assignment')
                    ->relationship('assignment', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        AssignmentSubmission::STATUS_SUBMITTED => 'Submitted',
                        AssignmentSubmission::STATUS_GRADED => 'Graded',
                        AssignmentSubmission::STATUS_RESUBMISSION_REQUIRED => 'Resubmission required',
                    ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssignmentSubmissions::route('/'),
            'edit' => EditAssignmentSubmission::route('/{record}/edit'),
        ];
    }
}




