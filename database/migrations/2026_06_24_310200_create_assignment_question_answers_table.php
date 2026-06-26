<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignment_question_answers')) {
            Schema::create('assignment_question_answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('assignment_submission_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assignment_question_id')->constrained()->cascadeOnDelete();
                $table->text('answer')->nullable();
                $table->timestamps();

                $table->unique(['assignment_submission_id', 'assignment_question_id'], 'assignment_answer_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_question_answers');
    }
};
