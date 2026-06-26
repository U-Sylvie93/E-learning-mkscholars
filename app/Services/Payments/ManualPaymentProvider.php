<?php

namespace App\Services\Payments;

use App\Models\Payment;

class ManualPaymentProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return Payment::PROVIDER_MANUAL;
    }

    public function label(): string
    {
        return 'Manual';
    }

    public function createPendingPayment(array $attributes): Payment
    {
        return Payment::create(array_merge($attributes, [
            'provider' => Payment::PROVIDER_MANUAL,
            'status' => Payment::STATUS_PENDING,
        ]));
    }

    public function supportsCallbacks(): bool
    {
        return false;
    }
}
