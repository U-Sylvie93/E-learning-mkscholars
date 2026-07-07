<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AdminAccountSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Account Settings';

    protected static ?string $slug = 'account-settings';

    protected string $view = 'filament.pages.admin-account-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    protected function getViewData(): array
    {
        return [
            'user' => auth()->user(),
            'profileRoute' => route('admin.account-settings.profile'),
            'passwordRoute' => route('admin.account-settings.password'),
        ];
    }
}
