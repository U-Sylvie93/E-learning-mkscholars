<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LiveClassReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Live Class Report';

    protected static ?string $slug = 'reports/live-classes';

    protected string $view = 'filament.pages.admin-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->role === User::ROLE_ADMIN
            || ($user?->role === User::ROLE_VIEWER && $user->hasViewerPermission(User::VIEWER_PERMISSION_REPORTS));
    }

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->liveClasses(request()->only(['from', 'to', 'course_id', 'status']));
    }
}
