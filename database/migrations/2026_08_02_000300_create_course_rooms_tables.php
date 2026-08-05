<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('course_room_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['course_room_id', 'created_at'], 'course_room_messages_room_created_idx');
        });

        Schema::create('course_room_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['course_room_id', 'user_id'], 'course_room_reads_room_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_room_reads');
        Schema::dropIfExists('course_room_messages');
        Schema::dropIfExists('course_rooms');
    }
};
