<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_courses');
    }
};
