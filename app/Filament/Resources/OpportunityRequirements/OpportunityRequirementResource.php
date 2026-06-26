<?php

namespace App\Filament\Resources\OpportunityRequirements;

use App\Filament\Resources\OpportunityRequirements\Pages\CreateOpportunityRequirement;
use App\Filament\Resources\OpportunityRequirements\Pages\EditOpportunityRequirement;
use App\Filament\Resources\OpportunityRequirements\Pages\ListOpportunityRequirements;
use App\Models\OpportunityRequirement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class OpportunityRequirementResource extends Resource
{
    protected static ?string $model = OpportunityRequirement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Opportunity Requirements';

    protected static string|UnitEnum|null $navigationGroup = 'Opportunities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('opportunity_id')
                    ->relationship('opportunity', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                Toggle::make('is_required')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opportunity.title')
                    ->label('Opportunity')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('opportunity')
                    ->relationship('opportunity', 'title')
                    ->searchable()
                    ->preload(),
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
            'index' => ListOpportunityRequirements::route('/'),
            'create' => CreateOpportunityRequirement::route('/create'),
            'edit' => EditOpportunityRequirement::route('/{record}/edit'),
        ];
    }
}
