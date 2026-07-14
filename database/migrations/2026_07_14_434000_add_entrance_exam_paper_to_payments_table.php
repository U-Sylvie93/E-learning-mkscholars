<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'entrance_exam_past_paper_id')) {
                $table->foreignId('entrance_exam_past_paper_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained('entrance_exam_past_papers')
                    ->nullOnDelete();

                $table->index(['user_id', 'entrance_exam_past_paper_id', 'purpose', 'status'], 'payments_entrance_exam_access_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'entrance_exam_past_paper_id')) {
                $table->dropForeign(['entrance_exam_past_paper_id']);
                $table->dropIndex('payments_entrance_exam_access_idx');
                $table->dropColumn('entrance_exam_past_paper_id');
            }
        });
    }
};
