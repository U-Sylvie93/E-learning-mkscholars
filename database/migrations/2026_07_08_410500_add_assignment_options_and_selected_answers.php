<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignment_options')) {
            Schema::create('assignment_options', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('assignment_question_id')->constrained()->cascadeOnDelete();
                $table->string('option_text');
                $table->boolean('is_correct')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['assignment_question_id', 'sort_order']);
            });
        }

        Schema::table('assignment_question_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('assignment_question_answers', 'selected_option_ids')) {
                $table->json('selected_option_ids')->nullable()->after('answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignment_question_answers', function (Blueprint $table): void {
            if (Schema::hasColumn('assignment_question_answers', 'selected_option_ids')) {
                $table->dropColumn('selected_option_ids');
            }
        });

        Schema::dropIfExists('assignment_options');
    }
};