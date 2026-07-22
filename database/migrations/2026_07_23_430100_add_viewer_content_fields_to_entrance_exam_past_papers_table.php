<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            if (! Schema::hasColumn('entrance_exam_past_papers', 'instructions')) {
                $table->longText('instructions')->nullable()->after('description');
            }

            if (! Schema::hasColumn('entrance_exam_past_papers', 'preview_file_path')) {
                $table->string('preview_file_path')->nullable()->after('paper_file_mime');
            }

            if (! Schema::hasColumn('entrance_exam_past_papers', 'preview_file_disk')) {
                $table->string('preview_file_disk', 32)->default('public')->after('preview_file_path');
            }

            if (! Schema::hasColumn('entrance_exam_past_papers', 'preview_file_mime')) {
                $table->string('preview_file_mime', 120)->nullable()->after('preview_file_disk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            foreach (['preview_file_mime', 'preview_file_disk', 'preview_file_path', 'instructions'] as $column) {
                if (Schema::hasColumn('entrance_exam_past_papers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
