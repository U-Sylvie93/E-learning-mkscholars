<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function key(): string;

    public function label(): string;

    public function createPendingPayment(array $attributes): Payment;

    public function supportsCallbacks(): bool;
}
