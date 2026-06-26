<?php

namespace App\Filament\Resources\Modules;

use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Models\Course;
use App\Models\Module;
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

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Modules';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Textarea::make('summary')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->required()
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->default(Course::STATUS_DRAFT),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label('Lessons')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        Course::STATUS_DRAFT => 'Draft',
                        Course::STATUS_PUBLISHED => 'Published',
                        Course::STATUS_ARCHIVED => 'Archived',
                    ]),
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
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'edit' => EditModule::route('/{record}/edit'),
        ];
    }
}
