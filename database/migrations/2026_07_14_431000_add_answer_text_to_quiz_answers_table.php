<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('quiz_answers', 'answer_text')) {
                $table->text('answer_text')->nullable()->after('selected_option_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            if (Schema::hasColumn('quiz_answers', 'answer_text')) {
                $table->dropColumn('answer_text');
            }
        });
    }
};
