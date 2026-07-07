<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('quiz_attempts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('started_at');
            }

            if (! Schema::hasColumn('quiz_attempts', 'current_question_index')) {
                $table->unsignedInteger('current_question_index')->default(0)->after('submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            if (Schema::hasColumn('quiz_attempts', 'current_question_index')) {
                $table->dropColumn('current_question_index');
            }

            if (Schema::hasColumn('quiz_attempts', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
