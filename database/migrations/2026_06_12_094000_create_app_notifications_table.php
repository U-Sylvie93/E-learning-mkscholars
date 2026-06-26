<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('role', 32)->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type', 32)->default('info');
            $table->string('category', 64)->default('system');
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['role', 'read_at']);
            $table->index(['category', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
