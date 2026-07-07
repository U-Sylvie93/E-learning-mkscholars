<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ProtectsReadOnlyViewers
{
    public static function isReadOnlyViewer(): bool
    {
        return auth()->user()?->role === User::ROLE_VIEWER;
    }

    public static function canCreate(): bool
    {
        return ! static::isReadOnlyViewer();
    }

    public static function canEdit(Model $record): bool
    {
        return ! static::isReadOnlyViewer();
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isReadOnlyViewer();
    }

    public static function canDeleteAny(): bool
    {
        return ! static::isReadOnlyViewer();
    }
}
