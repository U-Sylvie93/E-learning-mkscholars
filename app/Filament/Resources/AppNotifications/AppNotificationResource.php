<?php

namespace App\Filament\Resources\AppNotifications;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\AppNotifications\Pages\CreateAppNotification;
use App\Filament\Resources\AppNotifications\Pages\EditAppNotification;
use App\Filament\Resources\AppNotifications\Pages\ListAppNotifications;
use App\Models\AppNotification;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class AppNotificationResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = AppNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Target user')
                ->relationship('user', 'name')
                ->searchable()
                ->preload(),
            Select::make('role')
                ->label('Target role')
                ->options(self::roleOptions())
                ->helperText('When creating a role notification, MK Scholars creates one notification for each current user in that role.'),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('message')->required()->rows(5)->columnSpanFull(),
            Select::make('type')
                ->required()
                ->options(self::typeOptions())
                ->default(AppNotification::TYPE_INFO),
            Select::make('category')
                ->required()
                ->options(self::categoryOptions())
                ->default(AppNotification::CATEGORY_SYSTEM),
            TextInput::make('action_url')->maxLength(255),
            DateTimePicker::make('expires_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable()->placeholder('Role target'),
                TextColumn::make('role')->badge()->placeholder('Direct'),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('read_at')->dateTime()->placeholder('Unread')->sortable(),
                TextColumn::make('expires_at')->dateTime()->placeholder('No expiry')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::typeOptions()),
                SelectFilter::make('category')->options(self::categoryOptions()),
                SelectFilter::make('role')->options(self::roleOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppNotifications::route('/'),
            'create' => CreateAppNotification::route('/create'),
            'edit' => EditAppNotification::route('/{record}/edit'),
        ];
    }

    public static function roleOptions(): array
    {
        return collect(User::ROLES)
            ->mapWithKeys(fn (string $role): array => [$role => str($role)->headline()->toString()])
            ->all();
    }

    public static function typeOptions(): array
    {
        return collect(AppNotification::TYPES)
            ->mapWithKeys(fn (string $type): array => [$type => str($type)->headline()->toString()])
            ->all();
    }

    public static function categoryOptions(): array
    {
        return collect(AppNotification::CATEGORIES)
            ->mapWithKeys(fn (string $category): array => [$category => str($category)->replace('_', ' ')->headline()->toString()])
            ->all();
    }
}
