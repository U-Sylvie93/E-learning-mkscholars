<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_check_ins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mentor_assignment_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('topic');
            $table->text('student_notes')->nullable();
            $table->text('mentor_feedback')->nullable();
            $table->string('status', 32)->default('scheduled')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_check_ins');
    }
};
