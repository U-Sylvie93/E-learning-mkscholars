<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PaymentReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Payment Report';

    protected static ?string $slug = 'reports/payments';

    protected string $view = 'filament.pages.admin-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->role === User::ROLE_ADMIN
            || ($user?->role === User::ROLE_VIEWER && $user->hasViewerPermission(User::VIEWER_PERMISSION_REPORTS));
    }

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->payments(request()->only(['from', 'to', 'course_id', 'status']));
    }
}
