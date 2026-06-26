<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignment_questions')) {
            Schema::create('assignment_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
                $table->text('question_text');
                $table->string('question_type', 32)->default('text');
                $table->unsignedInteger('points')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->index(['assignment_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_questions');
    }
};
