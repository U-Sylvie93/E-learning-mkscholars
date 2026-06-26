<?php

namespace App\Filament\Pages;

use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CertificateReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Certificate Report';

    protected static ?string $slug = 'reports/certificates';

    protected string $view = 'filament.pages.admin-report';

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->certificates(request()->only(['from', 'to', 'course_id', 'status']));
    }
}
