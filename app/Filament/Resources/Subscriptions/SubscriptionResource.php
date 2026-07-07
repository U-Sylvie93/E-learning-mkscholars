<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Subscription;
use Filament\Actions\Action;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SubscriptionResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('student')->content(fn (?Subscription $record): string => $record?->user?->name ?? 'Student'),
            Placeholder::make('plan')->content(fn (?Subscription $record): string => $record?->subscriptionPlan?->name ?? 'Plan'),
            Placeholder::make('payment')->content(fn (?Subscription $record): string => $record?->payment ? '#'.$record->payment->id.' '.$record->payment->status : 'No payment'),
            Select::make('status')->required()->options(self::statusOptions()),
            DateTimePicker::make('starts_at'),
            DateTimePicker::make('ends_at'),
            DateTimePicker::make('cancelled_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Student')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('subscriptionPlan.name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('payment.amount')->label('Amount')->money('RWF')->placeholder('N/A'),
                TextColumn::make('payment.status')->label('Payment')->badge()->placeholder('No payment'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable()->placeholder('Not started'),
                TextColumn::make('ends_at')->dateTime()->sortable()->placeholder('No expiry'),
                TextColumn::make('expiry_state')
                    ->label('Expiry')
                    ->state(fn (Subscription $record): string => $record->isExpired() ? 'Expired' : ($record->isExpiringSoon() ? 'Expiring soon' : 'Current'))
                    ->badge(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
                SelectFilter::make('subscription_plan_id')
                    ->label('Plan')
                    ->relationship('subscriptionPlan', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('expired')
                    ->label('Expired by date')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('ends_at')->where('ends_at', '<', now())),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()->visible(fn (): bool => ! self::isReadOnlyViewer()),
                Action::make('cancel')
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $record): bool => ! self::isReadOnlyViewer() && in_array($record->status, [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE], true))
                    ->action(fn (Subscription $record) => $record->update([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ])),
                Action::make('markExpired')
                    ->label('Mark expired')
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $record): bool => ! self::isReadOnlyViewer() && $record->status === Subscription::STATUS_ACTIVE)
                    ->action(fn (Subscription $record) => $record->update([
                        'status' => Subscription::STATUS_EXPIRED,
                        'ends_at' => $record->ends_at && $record->ends_at->isPast() ? $record->ends_at : now(),
                    ])),
                Action::make('extend')
                    ->label('Extend')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('days')
                            ->numeric()
                            ->minValue(1)
                            ->default(30)
                            ->required(),
                    ])
                    ->visible(fn (Subscription $record): bool => ! self::isReadOnlyViewer() && in_array($record->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED], true))
                    ->action(function (Subscription $record, array $data): void {
                        $base = $record->ends_at && $record->ends_at->isFuture() ? $record->ends_at : now();

                        $record->update([
                            'status' => Subscription::STATUS_ACTIVE,
                            'starts_at' => $record->starts_at ?? now(),
                            'ends_at' => $base->copy()->addDays((int) $data['days']),
                            'cancelled_at' => null,
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            Subscription::STATUS_PENDING => 'Pending',
            Subscription::STATUS_ACTIVE => 'Active',
            Subscription::STATUS_EXPIRED => 'Expired',
            Subscription::STATUS_CANCELLED => 'Cancelled',
            Subscription::STATUS_REJECTED => 'Rejected',
        ];
    }
}
