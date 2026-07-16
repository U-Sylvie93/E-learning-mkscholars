<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrance_exam_institutions', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_institutions', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });

        Schema::table('entrance_exam_programs', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_programs', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });

        Schema::table('entrance_exam_subjects', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_subjects', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });

        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_past_papers', 'title')) {
                $table->string('title')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('entrance_exam_institutions', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_institutions', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
        });

        Schema::table('entrance_exam_programs', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_programs', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
        });

        Schema::table('entrance_exam_subjects', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_subjects', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
        });

        Schema::table('entrance_exam_past_papers', function (Blueprint $table): void {
            if (Schema::hasColumn('entrance_exam_past_papers', 'title')) {
                $table->string('title')->nullable(false)->change();
            }
        });
    }
};
