<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->string('submission_type', 32)->default('text');
            $table->unsignedInteger('max_score')->default(100);
            $table->unsignedInteger('due_days_after_enrollment')->nullable();
            $table->boolean('allow_late_submission')->default(true);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
