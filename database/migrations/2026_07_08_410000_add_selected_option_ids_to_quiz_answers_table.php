<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('quiz_answers', 'selected_option_ids')) {
                $table->json('selected_option_ids')->nullable()->after('quiz_option_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            if (Schema::hasColumn('quiz_answers', 'selected_option_ids')) {
                $table->dropColumn('selected_option_ids');
            }
        });
    }
};
