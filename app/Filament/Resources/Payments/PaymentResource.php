<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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

class PaymentResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('student')->content(fn (?Payment $record): string => $record?->user?->name ?? 'Student'),
            Placeholder::make('course')->content(fn (?Payment $record): string => $record?->course?->title ?? 'N/A'),
            Placeholder::make('subscription')->content(fn (?Payment $record): string => $record?->subscription?->subscriptionPlan?->name ?? 'N/A'),
            Placeholder::make('provider')->content(fn (?Payment $record): string => $record?->providerLabel() ?? 'Manual'),
            Placeholder::make('provider_reference')->content(fn (?Payment $record): string => $record?->provider_reference ?: 'N/A'),
            Placeholder::make('provider_status')->content(fn (?Payment $record): string => $record?->provider_status ?: 'N/A'),
            Select::make('payment_method_id')->relationship('paymentMethod', 'name')->searchable()->preload(),
            TextInput::make('amount')->numeric()->required(),
            TextInput::make('currency')->required()->maxLength(8),
            Select::make('purpose')->required()->options([
                Payment::PURPOSE_COURSE => 'Course',
                Payment::PURPOSE_SUBSCRIPTION => 'Subscription',
                Payment::PURPOSE_OTHER => 'Other',
            ]),
            Select::make('status')->required()->options(self::statusOptions()),
            TextInput::make('reference')->maxLength(255),
            FileUpload::make('proof_path')
                ->label('Proof file')
                ->disk('public')
                ->directory('payment-proofs')
                ->openable()
                ->downloadable()
                ->maxSize(10240)
                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg']),
            Textarea::make('admin_notes')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Student')->searchable()->sortable(),
                TextColumn::make('course.title')->label('Course')->searchable()->sortable()->placeholder('N/A'),
                TextColumn::make('subscription.subscriptionPlan.name')->label('Subscription')->searchable()->placeholder('N/A'),
                TextColumn::make('purpose')->badge()->sortable(),
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Payment $record): string => $record->providerLabel())
                    ->sortable(),
                TextColumn::make('provider_reference')->label('Provider ref')->placeholder('N/A')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('RWF')->sortable(),
                TextColumn::make('currency')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('paymentMethod.name')->label('Method')->placeholder('Not selected'),
                TextColumn::make('submitted_at')->dateTime()->sortable()->placeholder('Not submitted'),
                TextColumn::make('reviewed_at')->dateTime()->sortable()->placeholder('Not reviewed'),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
                SelectFilter::make('purpose')->options([
                    Payment::PURPOSE_COURSE => 'Course',
                    Payment::PURPOSE_SUBSCRIPTION => 'Subscription',
                    Payment::PURPOSE_OTHER => 'Other',
                ]),
                SelectFilter::make('provider')->options(self::providerOptions()),
                SelectFilter::make('course')->relationship('course', 'title')->searchable()->preload(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()->visible(fn (): bool => ! self::isReadOnlyViewer()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            Payment::STATUS_PENDING => 'Pending',
            Payment::STATUS_SUBMITTED => 'Submitted',
            Payment::STATUS_APPROVED => 'Approved',
            Payment::STATUS_REJECTED => 'Rejected',
            Payment::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function providerOptions(): array
    {
        return [
            Payment::PROVIDER_MANUAL => 'Manual',
            Payment::PROVIDER_MTN_MOMO => 'MTN MoMo',
            Payment::PROVIDER_AIRTEL_MONEY => 'Airtel Money',
            Payment::PROVIDER_STRIPE => 'Stripe',
            Payment::PROVIDER_PAYPAL => 'PayPal',
        ];
    }
}
