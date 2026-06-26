<?php

namespace App\Filament\Pages;

use App\Services\AdminReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StudentReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Student Report';

    protected static ?string $slug = 'reports/students';

    protected string $view = 'filament.pages.admin-report';

    protected function getViewData(): array
    {
        return app(AdminReportService::class)->students(request()->only(['from', 'to', 'course_id', 'status']));
    }
}
