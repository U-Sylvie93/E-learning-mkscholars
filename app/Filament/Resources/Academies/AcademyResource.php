<?php

namespace App\Filament\Resources\Academies;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Academies\Pages\CreateAcademy;
use App\Filament\Resources\Academies\Pages\EditAcademy;
use App\Filament\Resources\Academies\Pages\ListAcademies;
use App\Models\Academy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AcademyResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = Academy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Academies';

    protected static string|UnitEnum|null $navigationGroup = 'Learning';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Academy details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('summary')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(6)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->required()
                            ->options([
                                Academy::STATUS_DRAFT => 'Draft',
                                Academy::STATUS_PUBLISHED => 'Published',
                                Academy::STATUS_ARCHIVED => 'Archived',
                            ])
                            ->default(Academy::STATUS_DRAFT),
                    ])
                    ->columns(2),
                Section::make('Visual presentation')
                    ->description('Choose a safe academy icon and upload an optional public image for academy cards.')
                    ->schema([
                        Select::make('icon')
                            ->label('Academy icon')
                            ->options(Academy::iconOptions())
                            ->default(Academy::ICON_BOOK_OPEN)
                            ->searchable()
                            ->native(false)
                            ->helperText('Used on academy cards and course academy labels.'),
                        FileUpload::make('image_path')
                            ->label('Academy image')
                            ->disk('public')
                            ->directory('academies')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(4096)
                            ->imagePreviewHeight('140')
                            ->openable()
                            ->downloadable()
                            ->helperText('JPG, PNG, or WebP. Max 4MB. Empty images use a local navy/gold icon fallback on public pages.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('icon')
                    ->formatStateUsing(fn (?string $state): string => Academy::iconOptions()[$state] ?? 'Book Open')
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('courses_count')
                    ->counts('courses')
                    ->label('Courses')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Academy::STATUS_DRAFT => 'Draft',
                        Academy::STATUS_PUBLISHED => 'Published',
                        Academy::STATUS_ARCHIVED => 'Archived',
                    ]),
                SelectFilter::make('icon')
                    ->options(Academy::iconOptions()),
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
            'index' => ListAcademies::route('/'),
            'create' => CreateAcademy::route('/create'),
            'edit' => EditAcademy::route('/{record}/edit'),
        ];
    }
}

