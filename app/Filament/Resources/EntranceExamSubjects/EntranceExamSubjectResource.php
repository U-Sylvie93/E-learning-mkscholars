<?php

namespace App\Filament\Resources\EntranceExamSubjects;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\EntranceExamSubjects\Pages\CreateEntranceExamSubject;
use App\Filament\Resources\EntranceExamSubjects\Pages\EditEntranceExamSubject;
use App\Filament\Resources\EntranceExamSubjects\Pages\ListEntranceExamSubjects;
use App\Models\EntranceExamSubject;
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

class EntranceExamSubjectResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = EntranceExamSubject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Exam Subjects';

    protected static string|UnitEnum|null $navigationGroup = 'Entrance Exam Academy';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Leave blank to generate from the name.'),
            Select::make('status')
                ->required()
                ->options(EntranceExamSubject::statusOptions())
                ->default(EntranceExamSubject::STATUS_ACTIVE),
            Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('past_papers_count')->counts('pastPapers')->label('Papers')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(EntranceExamSubject::statusOptions()),
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
            'index' => ListEntranceExamSubjects::route('/'),
            'create' => CreateEntranceExamSubject::route('/create'),
            'edit' => EditEntranceExamSubject::route('/{record}/edit'),
        ];
    }
}
