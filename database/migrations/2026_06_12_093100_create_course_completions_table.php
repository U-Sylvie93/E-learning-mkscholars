<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('lesson_percentage')->default(0);
            $table->unsignedTinyInteger('quiz_percentage')->default(0);
            $table->unsignedTinyInteger('assignment_percentage')->default(0);
            $table->unsignedTinyInteger('live_attendance_percentage')->nullable();
            $table->boolean('is_eligible_for_certificate')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_completions');
    }
};
