<?php

namespace App\Services\Payments;

use App\Models\Payment;
use InvalidArgumentException;

class PaymentProviderManager
{
    /**
     * @var array<string, class-string<PaymentProviderInterface>>
     */
    private array $providers = [
        Payment::PROVIDER_MANUAL => ManualPaymentProvider::class,
    ];

    public function driver(?string $provider = null): PaymentProviderInterface
    {
        $provider = $provider ?: Payment::PROVIDER_MANUAL;

        if (! array_key_exists($provider, $this->providers)) {
            throw new InvalidArgumentException('Payment provider ['.$provider.'] is not configured.');
        }

        return app($this->providers[$provider]);
    }

    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }
}
