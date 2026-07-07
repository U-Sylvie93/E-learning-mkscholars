<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Models\PaymentMethod;
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
use UnitEnum;

class PaymentMethodResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('type')
                ->required()
                ->options(self::typeOptions())
                ->default(PaymentMethod::TYPE_MOMO),
            TextInput::make('account_name')->maxLength(255),
            TextInput::make('account_number')->maxLength(255),
            Textarea::make('instructions')->rows(5)->columnSpanFull(),
            Select::make('status')
                ->required()
                ->options([
                    PaymentMethod::STATUS_ACTIVE => 'Active',
                    PaymentMethod::STATUS_INACTIVE => 'Inactive',
                ])
                ->default(PaymentMethod::STATUS_ACTIVE),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('account_number')->searchable()->placeholder('Not set'),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::typeOptions()),
                SelectFilter::make('status')->options([
                    PaymentMethod::STATUS_ACTIVE => 'Active',
                    PaymentMethod::STATUS_INACTIVE => 'Inactive',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            PaymentMethod::TYPE_MOMO => 'MTN MoMo',
            PaymentMethod::TYPE_AIRTEL => 'Airtel Money',
            PaymentMethod::TYPE_BANK => 'Bank',
            PaymentMethod::TYPE_CASH => 'Cash',
            PaymentMethod::TYPE_CARD => 'Card',
            PaymentMethod::TYPE_OTHER => 'Other',
        ];
    }
}
