<?php

namespace App\Filament\Widgets;

use App\Models\AppNotification;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'MK Scholars overview';

    protected ?string $description = 'Core platform activity at a glance.';

    protected function getStats(): array
    {
        return [
            Stat::make('Students', User::query()->where('role', User::ROLE_STUDENT)->count())
                ->description('Registered learners'),
            Stat::make('Courses', Course::query()->count())
                ->description('All course records'),
            Stat::make('Pending payments', Payment::query()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])->count())
                ->description('Need review'),
            Stat::make('Certificates', Certificate::query()->where('status', Certificate::STATUS_ISSUED)->count())
                ->description('Issued credentials'),
            Stat::make('Notifications', AppNotification::query()->whereNull('read_at')->count())
                ->description('Unread in-app notifications'),
        ];
    }
}
