<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecureSubscriptionFormTest extends TestCase
{
    public function test_subscription_and_payment_views_do_not_hardcode_insecure_internal_form_actions(): void
    {
        $views = [
            resource_path('views/pages/pricing.blade.php'),
            resource_path('views/student/subscriptions.blade.php'),
            resource_path('views/student/subscription-show.blade.php'),
            resource_path('views/student/payment-show.blade.php'),
        ];

        foreach ($views as $view) {
            $contents = file_get_contents($view);

            $this->assertStringNotContainsString('action="http://', $contents, $view.' contains an insecure form action.');
            $this->assertStringNotContainsString("action='http://", $contents, $view.' contains an insecure form action.');
            $this->assertStringNotContainsString('http://e-learning.mkscholars.com', $contents, $view.' contains the internal HTTP production URL.');
        }
    }

    public function test_production_url_generation_forces_https_scheme(): void
    {
        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\URL;', $provider);
        $this->assertStringContainsString("app()->environment('production')", $provider);
        $this->assertStringContainsString("URL::forceScheme('https')", $provider);
    }
}
