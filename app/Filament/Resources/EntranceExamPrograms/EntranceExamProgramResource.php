<?php

namespace App\Filament\Resources\EntranceExamPrograms;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\EntranceExamPrograms\Pages\CreateEntranceExamProgram;
use App\Filament\Resources\EntranceExamPrograms\Pages\EditEntranceExamProgram;
use App\Filament\Resources\EntranceExamPrograms\Pages\ListEntranceExamPrograms;
use App\Models\EntranceExamProgram;
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
use Illuminate\Support\Str;
use UnitEnum;

class EntranceExamProgramResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = EntranceExamProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Exam Programs';

    protected static string|UnitEnum|null $navigationGroup = 'Entrance Exam Academy';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('entrance_exam_institution_id')
                ->label('Institution')
                ->relationship('institution', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Leave blank to generate from the name.'),
            Select::make('status')
                ->required()
                ->options(EntranceExamProgram::statusOptions())
                ->default(EntranceExamProgram::STATUS_ACTIVE),
            Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('institution.name')->label('Institution')->searchable()->sortable()->placeholder('General'),
                TextColumn::make('past_papers_count')->counts('pastPapers')->label('Papers')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('institution')->relationship('institution', 'name')->searchable()->preload(),
                SelectFilter::make('status')->options(EntranceExamProgram::statusOptions()),
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
            'index' => ListEntranceExamPrograms::route('/'),
            'create' => CreateEntranceExamProgram::route('/create'),
            'edit' => EditEntranceExamProgram::route('/{record}/edit'),
        ];
    }
}
