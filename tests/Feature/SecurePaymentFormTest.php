<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurePaymentFormTest extends TestCase
{
    public function test_payment_view_does_not_hardcode_insecure_internal_form_actions(): void
    {
        $view = resource_path('views/student/payment-show.blade.php');
        $contents = file_get_contents($view);

        $this->assertStringNotContainsString('action="http://', $contents);
        $this->assertStringNotContainsString("action='http://", $contents);
        $this->assertStringNotContainsString('http://e-learning.mkscholars.com', $contents);
    }

    public function test_production_url_generation_forces_https_scheme(): void
    {
        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\URL;', $provider);
        $this->assertStringContainsString("app()->environment('production')", $provider);
        $this->assertStringContainsString("URL::forceScheme('https')", $provider);
    }
}
