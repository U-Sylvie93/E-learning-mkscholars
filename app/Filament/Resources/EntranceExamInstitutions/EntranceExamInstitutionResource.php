<?php

namespace App\Filament\Resources\EntranceExamInstitutions;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\EntranceExamInstitutions\Pages\CreateEntranceExamInstitution;
use App\Filament\Resources\EntranceExamInstitutions\Pages\EditEntranceExamInstitution;
use App\Filament\Resources\EntranceExamInstitutions\Pages\ListEntranceExamInstitutions;
use App\Models\EntranceExamInstitution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class EntranceExamInstitutionResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = EntranceExamInstitution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Exam Institutions';

    protected static string|UnitEnum|null $navigationGroup = 'Entrance Exam Academy';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Leave blank to generate from the name.'),
            TextInput::make('country')->maxLength(120),
            Select::make('status')
                ->required()
                ->options(EntranceExamInstitution::statusOptions())
                ->default(EntranceExamInstitution::STATUS_ACTIVE),
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->disk('public')
                ->directory('entrance-exam/institutions')
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                ->maxSize(2048)
                ->openable()
                ->downloadable()
                ->columnSpanFull(),
            Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')->label('Logo')->disk('public')->square(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country')->searchable()->sortable(),
                TextColumn::make('programs_count')->counts('programs')->label('Programs')->sortable(),
                TextColumn::make('past_papers_count')->counts('pastPapers')->label('Papers')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(EntranceExamInstitution::statusOptions()),
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
            'index' => ListEntranceExamInstitutions::route('/'),
            'create' => CreateEntranceExamInstitution::route('/create'),
            'edit' => EditEntranceExamInstitution::route('/{record}/edit'),
        ];
    }
}
