<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('verification_code')->unique();
            $table->string('student_name');
            $table->string('course_title');
            $table->unsignedInteger('score')->nullable();
            $table->string('status', 32)->default('issued')->index();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
