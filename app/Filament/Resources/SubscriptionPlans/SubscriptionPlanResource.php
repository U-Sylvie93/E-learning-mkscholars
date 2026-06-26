<?php

namespace App\Filament\Resources\SubscriptionPlans;

use App\Filament\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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
            Textarea::make('description')->rows(4)->columnSpanFull(),
            TextInput::make('price_amount')->required()->numeric()->minValue(0),
            TextInput::make('currency')->required()->default('RWF')->maxLength(8),
            Select::make('billing_cycle')
                ->required()
                ->options(self::billingOptions())
                ->default(SubscriptionPlan::BILLING_MONTHLY),
            TextInput::make('duration_days')
                ->numeric()
                ->minValue(1)
                ->helperText('Optional. Defaults to 30 days for monthly/custom and 365 days for yearly.'),
            Select::make('status')
                ->required()
                ->options(self::statusOptions())
                ->default(SubscriptionPlan::STATUS_ACTIVE),
            Select::make('courses')
                ->relationship('courses', 'title')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
            TagsInput::make('features')
                ->placeholder('Add a plan feature')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('price_amount')->money('RWF')->sortable(),
                TextColumn::make('billing_cycle')->badge()->sortable(),
                TextColumn::make('duration_days')->label('Days')->placeholder('Default'),
                TextColumn::make('courses_count')->counts('courses')->label('Courses')->sortable(),
                TextColumn::make('subscriptions_count')->counts('subscriptions')->label('Subscriptions')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('billing_cycle')->options(self::billingOptions()),
                SelectFilter::make('status')->options(self::statusOptions()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPlans::route('/'),
            'create' => CreateSubscriptionPlan::route('/create'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }

    public static function billingOptions(): array
    {
        return [
            SubscriptionPlan::BILLING_MONTHLY => 'Monthly',
            SubscriptionPlan::BILLING_YEARLY => 'Yearly',
            SubscriptionPlan::BILLING_CUSTOM => 'Custom',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            SubscriptionPlan::STATUS_ACTIVE => 'Active',
            SubscriptionPlan::STATUS_INACTIVE => 'Inactive',
            SubscriptionPlan::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
