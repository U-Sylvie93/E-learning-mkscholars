<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table): void {
            if (! Schema::hasColumn('modules', 'slug')) {
                $table->string('slug')->after('title')->nullable();
            }
        });

        Schema::table('lessons', function (Blueprint $table): void {
            if (! Schema::hasColumn('lessons', 'slug')) {
                $table->string('slug')->after('title')->nullable();
            }

            if (! Schema::hasColumn('lessons', 'lesson_type')) {
                $table->string('lesson_type', 32)->default('text')->after('summary');
            }

            if (! Schema::hasColumn('lessons', 'video_url')) {
                $table->string('video_url')->nullable()->after('lesson_type');
            }

            if (! Schema::hasColumn('lessons', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('content');
            }

            if (! Schema::hasColumn('lessons', 'is_free_preview')) {
                $table->boolean('is_free_preview')->default(false)->after('sort_order');
            }
        });

        Schema::table('lesson_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('lesson_activities', 'activity_type')) {
                $table->string('activity_type', 32)->default('video')->after('lesson_id');
            }

            if (! Schema::hasColumn('lesson_activities', 'resource_url')) {
                $table->string('resource_url')->nullable()->after('instructions');
            }
        });
    }

    public function down(): void
    {
        //
    }
};
