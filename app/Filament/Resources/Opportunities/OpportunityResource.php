<?php

namespace App\Filament\Resources\Opportunities;

use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Models\Opportunity;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Opportunities';

    protected static string|UnitEnum|null $navigationGroup = 'Opportunities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state))
                        : null)
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->required()
                    ->options(self::typeOptions())
                    ->default(Opportunity::TYPE_SCHOLARSHIP),
                Select::make('status')
                    ->required()
                    ->options(self::statusOptions())
                    ->default(Opportunity::STATUS_DRAFT),
                TextInput::make('organization')
                    ->maxLength(255),
                TextInput::make('country')
                    ->maxLength(255),
                TextInput::make('city')
                    ->maxLength(255),
                TextInput::make('application_url')
                    ->url()
                    ->maxLength(255),
                DatePicker::make('deadline'),
                Toggle::make('is_featured')
                    ->default(false),
                Textarea::make('description')
                    ->required()
                    ->rows(7)
                    ->columnSpanFull(),
                Textarea::make('requirements')
                    ->rows(5)
                    ->columnSpanFull(),
                Textarea::make('benefits')
                    ->rows(5)
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
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('organization')
                    ->searchable()
                    ->placeholder('Not set'),
                TextColumn::make('country')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Global'),
                TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->placeholder('Open'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                TextColumn::make('student_applications_count')
                    ->counts('studentApplications')
                    ->label('Applications')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::typeOptions()),
                SelectFilter::make('status')->options(self::statusOptions()),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            Opportunity::TYPE_SCHOLARSHIP => 'Scholarship',
            Opportunity::TYPE_INTERNSHIP => 'Internship',
            Opportunity::TYPE_JOB => 'Job',
            Opportunity::TYPE_STUDY_ABROAD => 'Study abroad',
            Opportunity::TYPE_COMPETITION => 'Competition',
            Opportunity::TYPE_EVENT => 'Event',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            Opportunity::STATUS_DRAFT => 'Draft',
            Opportunity::STATUS_PUBLISHED => 'Published',
            Opportunity::STATUS_CLOSED => 'Closed',
            Opportunity::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
