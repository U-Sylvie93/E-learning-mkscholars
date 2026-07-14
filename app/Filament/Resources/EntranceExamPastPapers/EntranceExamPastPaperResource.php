<?php

namespace App\Filament\Resources\EntranceExamPastPapers;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\EntranceExamPastPapers\Pages\CreateEntranceExamPastPaper;
use App\Filament\Resources\EntranceExamPastPapers\Pages\EditEntranceExamPastPaper;
use App\Filament\Resources\EntranceExamPastPapers\Pages\ListEntranceExamPastPapers;
use App\Models\EntranceExamPastPaper;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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

class EntranceExamPastPaperResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = EntranceExamPastPaper::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Past Papers';

    protected static string|UnitEnum|null $navigationGroup = 'Entrance Exam Academy';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Leave blank to generate from the title.'),
            Select::make('entrance_exam_institution_id')
                ->label('Institution')
                ->relationship('institution', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('entrance_exam_program_id')
                ->label('Program / Faculty')
                ->relationship('program', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('entrance_exam_subject_id')
                ->label('Subject')
                ->relationship('subject', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('exam_year')
                ->numeric()
                ->minValue(1900)
                ->maxValue((int) now()->addYear()->format('Y')),
            TextInput::make('exam_type')
                ->maxLength(120)
                ->placeholder('National entrance, mock, aptitude, placement'),
            Select::make('status')
                ->required()
                ->options(EntranceExamPastPaper::statusOptions())
                ->default(EntranceExamPastPaper::STATUS_DRAFT),
            Toggle::make('is_featured')->default(false),
            FileUpload::make('paper_file_path')
                ->label('Past paper PDF')
                ->helperText('PDF only. Max 20MB.')
                ->disk('public')
                ->directory('entrance-exam/past-papers')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(20480)
                ->required()
                ->openable()
                ->columnSpanFull(),
            Textarea::make('description')->rows(4)->columnSpanFull(),
            TextInput::make('page_count')->numeric()->minValue(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('institution.name')->label('Institution')->searchable()->sortable()->placeholder('General'),
                TextColumn::make('program.name')->label('Program')->searchable()->sortable()->placeholder('General'),
                TextColumn::make('subject.name')->label('Subject')->searchable()->sortable()->placeholder('General'),
                TextColumn::make('exam_year')->label('Year')->sortable(),
                TextColumn::make('exam_type')->badge()->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('uploadedBy.name')->label('Uploaded by')->placeholder('System')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(EntranceExamPastPaper::statusOptions()),
                SelectFilter::make('institution')->relationship('institution', 'name')->searchable()->preload(),
                SelectFilter::make('program')->relationship('program', 'name')->searchable()->preload(),
                SelectFilter::make('subject')->relationship('subject', 'name')->searchable()->preload(),
                SelectFilter::make('exam_year')
                    ->label('Year')
                    ->options(fn (): array => EntranceExamPastPaper::query()
                        ->whereNotNull('exam_year')
                        ->orderByDesc('exam_year')
                        ->pluck('exam_year', 'exam_year')
                        ->map(fn ($year): string => (string) $year)
                        ->all()),
                SelectFilter::make('exam_type')
                    ->options(fn (): array => EntranceExamPastPaper::query()
                        ->whereNotNull('exam_type')
                        ->orderBy('exam_type')
                        ->pluck('exam_type', 'exam_type')
                        ->all()),
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
            'index' => ListEntranceExamPastPapers::route('/'),
            'create' => CreateEntranceExamPastPaper::route('/create'),
            'edit' => EditEntranceExamPastPaper::route('/{record}/edit'),
        ];
    }
}
