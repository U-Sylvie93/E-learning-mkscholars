<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            if (! Schema::hasColumn('entrance_exam_past_papers', 'price_amount')) {
                $table->decimal('price_amount', 12, 2)->nullable()->default(0)->after('paper_file_mime');
            }

            if (! Schema::hasColumn('entrance_exam_past_papers', 'currency')) {
                $table->string('currency', 8)->default('RWF')->after('price_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_past_papers', 'currency')) {
                $table->dropColumn('currency');
            }

            if (Schema::hasColumn('entrance_exam_past_papers', 'price_amount')) {
                $table->dropColumn('price_amount');
            }
        });
    }
};
