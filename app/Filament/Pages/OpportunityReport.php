<?php

namespace App\Filament\Pages;

use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OpportunityReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Opportunity Report';

    protected static ?string $slug = 'reports/opportunities';

    protected string $view = 'filament.pages.admin-report';

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->opportunities(request()->only(['from', 'to', 'course_id', 'status']));
    }
}
