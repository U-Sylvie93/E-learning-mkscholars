<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\RelationManagers\ModulesRelationManager;
use App\Models\Course;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Courses';

    protected static string|UnitEnum|null $navigationGroup = 'Learning';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('instructor_id')
                    ->label('Instructor owner')
                    ->relationship(
                        name: 'instructor',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('role', User::ROLE_INSTRUCTOR)
                            ->where(fn ($q) => $q
                                ->where('approval_status', User::APPROVAL_APPROVED)
                                ->orWhereNull('approval_status')),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Optional owner for instructor course builder access. Only approved instructors are listed.'),
                TextInput::make('title')
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
                Textarea::make('short_description')
                    ->required()
                    ->maxLength(600)
                    ->rows(3)
                    ->columnSpanFull(),
                MarkdownEditor::make('full_description')
                    ->label('Course overview')
                    ->helperText('Use Markdown for headings, lists, links, images, and tables. Unsafe scripts and embeds are stripped on public pages.')
                    ->columnSpanFull(),
                TextInput::make('level')
                    ->required()
                    ->maxLength(80),
                TextInput::make('duration')
                    ->required()
                    ->maxLength(80),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->helperText('Legacy display price kept for compatibility. Use access pricing below for payments.'),
                Toggle::make('is_free')
                    ->label('Free course')
                    ->default(true),
                Select::make('access_type')
                    ->required()
                    ->options([
                        Course::ACCESS_FREE => 'Free',
                        Course::ACCESS_PAID => 'Paid',
                    ])
                    ->default(Course::ACCESS_FREE),
                TextInput::make('price_amount')
                    ->numeric()
                    ->minValue(0)
                    ->label('Price amount'),
                TextInput::make('currency')
                    ->required()
                    ->default('RWF')
                    ->maxLength(8),
                Select::make('status')
                    ->required()
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->default(Course::STATUS_DRAFT),
                FileUpload::make('featured_image_path')
                    ->label('Cover image')
                    ->helperText('Upload a course cover image for public course cards and the detail hero. JPG, PNG, and WebP up to 4MB are supported.')
                    ->image()
                    ->imagePreviewHeight('220')
                    ->openable()
                    ->downloadable()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('courses')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->columnSpanFull(),
                TagsInput::make('learning_outcomes')
                    ->placeholder('Add an outcome')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image_path')
                    ->label('Cover')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (Course $record): string => $record->coverImageUrl() ?? asset('images/mk-scholars-logo.webp')),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academy.name')
                    ->label('Academy')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->placeholder('Unassigned')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->sortable(),
                TextColumn::make('duration')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Legacy price')
                    ->sortable(),
                TextColumn::make('price_amount')
                    ->money('RWF')
                    ->label('Payment price')
                    ->sortable(),
                TextColumn::make('access_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('completionRule.status')
                    ->label('Completion rule')
                    ->badge()
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academy')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('instructor')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
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
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ModulesRelationManager::class,
        ];
    }
}



