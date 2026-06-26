<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_amount', 10, 2);
            $table->string('currency', 8)->default('RWF');
            $table->string('billing_cycle', 32)->default(SubscriptionPlan::BILLING_MONTHLY);
            $table->unsignedInteger('duration_days')->nullable();
            $table->string('status', 32)->default(SubscriptionPlan::STATUS_ACTIVE)->index();
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
