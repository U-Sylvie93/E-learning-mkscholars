<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === Subscription::STATUS_CANCELLED) {
            $data['cancelled_at'] ??= now();
        }

        if (($data['status'] ?? null) === Subscription::STATUS_ACTIVE) {
            $startsAt = $data['starts_at'] ?? now();
            $data['starts_at'] = $startsAt;
            $data['ends_at'] ??= $this->record->subscriptionPlan
                ? Carbon::parse($startsAt)->addDays($this->record->subscriptionPlan->durationDays())
                : null;
        }

        return $data;
    }
}
