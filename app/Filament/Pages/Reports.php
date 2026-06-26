<?php

namespace App\Filament\Pages;

use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Reports Overview';

    protected static ?string $slug = 'reports';

    protected string $view = 'filament.pages.admin-report';

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->index();
    }
}
