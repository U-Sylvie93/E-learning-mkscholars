<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('lesson_activities', 'resource_path')) {
                $table->string('resource_path')->nullable()->after('resource_url');
            }

            if (! Schema::hasColumn('lesson_activities', 'resource_disk')) {
                $table->string('resource_disk', 32)->nullable()->after('resource_path');
            }

            if (! Schema::hasColumn('lesson_activities', 'resource_mime')) {
                $table->string('resource_mime', 120)->nullable()->after('resource_disk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_activities', function (Blueprint $table): void {
            foreach (['resource_mime', 'resource_disk', 'resource_path'] as $column) {
                if (Schema::hasColumn('lesson_activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
