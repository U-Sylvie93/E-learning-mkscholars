<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_completion_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('required_lesson_percentage')->default(80);
            $table->boolean('require_all_lessons')->default(false);
            $table->unsignedTinyInteger('required_quiz_percentage')->default(50);
            $table->boolean('require_all_published_quizzes_passed')->default(false);
            $table->boolean('require_all_published_assignments_submitted')->default(false);
            $table->boolean('require_final_quiz_passed')->default(false);
            $table->foreignId('final_quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            $table->unsignedTinyInteger('required_live_class_attendance_percentage')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_completion_rules');
    }
};
