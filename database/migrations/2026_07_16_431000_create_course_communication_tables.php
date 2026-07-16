<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_message_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['course_id', 'student_id', 'instructor_id'], 'course_message_threads_unique_participants');
        });

        Schema::create('course_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_message_thread_id')->constrained('course_message_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['course_message_thread_id', 'created_at'], 'course_messages_thread_created_idx');
        });

        Schema::create('course_announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status', 32)->default('published')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['course_id', 'status', 'published_at'], 'course_announcements_course_status_published_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_announcements');
        Schema::dropIfExists('course_messages');
        Schema::dropIfExists('course_message_threads');
    }
};
