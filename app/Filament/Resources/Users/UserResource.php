<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\ProtectsReadOnlyViewers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AppNotification;
use App\Models\Course;
use App\Models\User;
use App\Services\AppNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    use ProtectsReadOnlyViewers;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Users';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->required()->maxLength(160)->unique(ignoreRecord: true),
            Select::make('role')
                ->required()
                ->options(self::roleOptions())
                ->disabled(fn (?User $record): bool => $record?->id === auth()->id()),
            Select::make('approval_status')
                ->label('Approval status')
                ->required()
                ->options(self::approvalOptions())
                ->disabled(fn (?User $record): bool => $record?->role === User::ROLE_ADMIN || $record?->id === auth()->id()),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->maxLength(255)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText('Set a temporary password for new admin/viewer accounts. Leave blank on edit to keep the current password.'),
            Select::make('viewer_permissions')
                ->label('Viewer allowed sections')
                ->multiple()
                ->options(User::viewerPermissionOptions())
                ->helperText('Viewer accounts remain read-only. Leave empty for minimal admin access with no resource sections.')
                ->visible(fn ($get): bool => $get('role') === User::ROLE_VIEWER),
            Select::make('content_permissions')
                ->label('Content editor permissions')
                ->multiple()
                ->options(User::contentPermissionOptions())
                ->helperText('Content editors can only use selected content tools and cannot manage users, payments, subscriptions, certificates, reports, or settings.')
                ->visible(fn ($get): bool => $get('role') === User::ROLE_CONTENT_EDITOR),
            Select::make('content_course_ids')
                ->label('Assigned courses')
                ->multiple()
                ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                ->searchable()
                ->preload()
                ->helperText('Content editors can edit only these courses and related modules, lessons, quizzes, final tests, and assignments.')
                ->visible(fn ($get): bool => $get('role') === User::ROLE_CONTENT_EDITOR),
            DateTimePicker::make('approved_at')->disabled()->dehydrated(false),
            Placeholder::make('approved_by_name')
                ->label('Approved by')
                ->content(fn (?User $record): string => $record?->approvedBy?->name ?? 'Not approved by an admin yet'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('role')->badge()->sortable(),
                TextColumn::make('approval_status')->label('Status')->badge()->sortable(),
                TextColumn::make('viewer_permissions')
                    ->label('Viewer sections')
                    ->formatStateUsing(fn ($state): string => is_array($state) && $state !== [] ? (string) count($state) : 'None')
                    ->toggleable(),
                TextColumn::make('content_permissions')
                    ->label('Content permissions')
                    ->formatStateUsing(fn ($state): string => is_array($state) && $state !== [] ? (string) count($state) : 'None')
                    ->toggleable(),
                TextColumn::make('approved_at')->dateTime()->placeholder('Not approved')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options(self::roleOptions()),
                SelectFilter::make('approval_status')->label('Approval status')->options(self::approvalOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! self::isReadOnlyViewer() && self::canModerate($record) && $record->approval_status !== User::APPROVAL_APPROVED)
                    ->action(fn (User $record) => self::setApprovalStatus($record, User::APPROVAL_APPROVED)),
                Action::make('reject')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! self::isReadOnlyViewer() && self::canModerate($record) && $record->approval_status !== User::APPROVAL_REJECTED)
                    ->action(fn (User $record) => self::setApprovalStatus($record, User::APPROVAL_REJECTED)),
                Action::make('suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! self::isReadOnlyViewer() && self::canModerate($record) && $record->approval_status !== User::APPROVAL_SUSPENDED)
                    ->action(fn (User $record) => self::setApprovalStatus($record, User::APPROVAL_SUSPENDED)),
                EditAction::make()->visible(fn (): bool => ! self::isReadOnlyViewer()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function roleOptions(): array
    {
        return collect(User::ROLES)
            ->mapWithKeys(fn (string $role): array => [$role => str($role)->headline()->toString()])
            ->all();
    }

    public static function approvalOptions(): array
    {
        return collect(User::APPROVAL_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => str($status)->headline()->toString()])
            ->all();
    }

    public static function canModerate(User $record): bool
    {
        return $record->role !== User::ROLE_ADMIN && $record->id !== auth()->id();
    }

    public static function setApprovalStatus(User $record, string $status): void
    {
        if (! self::canModerate($record) || ! in_array($status, User::APPROVAL_STATUSES, true)) {
            return;
        }

        $record->forceFill([
            'approval_status' => $status,
            'approved_at' => $status === User::APPROVAL_APPROVED ? now() : null,
            'approved_by' => $status === User::APPROVAL_APPROVED ? auth()->id() : null,
        ])->save();

        if (in_array($record->role, [User::ROLE_INSTRUCTOR, User::ROLE_MENTOR], true)) {
            app(AppNotificationService::class)->createForUser($record, [
                'title' => $status === User::APPROVAL_APPROVED ? 'Account approved' : 'Account status updated',
                'message' => match ($status) {
                    User::APPROVAL_APPROVED => 'Your MK Scholars '.$record->role.' account has been approved.',
                    User::APPROVAL_REJECTED => 'Your MK Scholars '.$record->role.' account was not approved.',
                    User::APPROVAL_SUSPENDED => 'Your MK Scholars '.$record->role.' account has been suspended.',
                    default => 'Your MK Scholars account status is pending review.',
                },
                'type' => $status === User::APPROVAL_APPROVED ? AppNotification::TYPE_SUCCESS : AppNotification::TYPE_WARNING,
                'category' => AppNotification::CATEGORY_SYSTEM,
                'created_by' => auth()->id(),
            ]);
        }
    }
}
