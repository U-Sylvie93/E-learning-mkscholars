<?php

namespace App\Filament\Resources\PasswordResetRequests;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\PasswordResetRequests\Pages\EditPasswordResetRequest;
use App\Filament\Resources\PasswordResetRequests\Pages\ListPasswordResetRequests;
use App\Models\PasswordResetRequest;
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

class PasswordResetRequestResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = PasswordResetRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Password Reset Requests';

    protected static string|UnitEnum|null $navigationGroup = 'Access';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->disabled(),
            TextInput::make('otp')
                ->label('One-time code (share with user)')
                ->disabled()
                ->helperText('Copy this code and share it with the requesting user. Codes expire 30 minutes after creation.'),
            TextInput::make('expires_at')->disabled(),
            TextInput::make('used_at')->disabled(),
            TextInput::make('request_ip')->label('Requested from IP')->disabled(),
            Select::make('status')
                ->required()
                ->options([
                    PasswordResetRequest::STATUS_PENDING => 'Pending',
                    PasswordResetRequest::STATUS_USED => 'Used',
                    PasswordResetRequest::STATUS_EXPIRED => 'Expired',
                    PasswordResetRequest::STATUS_REJECTED => 'Rejected',
                ]),
            Textarea::make('admin_notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('otp')->label('Code')->copyable()->badge()->color('warning')->weight('bold'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                TextColumn::make('used_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    PasswordResetRequest::STATUS_PENDING => 'Pending',
                    PasswordResetRequest::STATUS_USED => 'Used',
                    PasswordResetRequest::STATUS_EXPIRED => 'Expired',
                    PasswordResetRequest::STATUS_REJECTED => 'Rejected',
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
            'index' => ListPasswordResetRequests::route('/'),
            'edit' => EditPasswordResetRequest::route('/{record}/edit'),
        ];
    }
}
