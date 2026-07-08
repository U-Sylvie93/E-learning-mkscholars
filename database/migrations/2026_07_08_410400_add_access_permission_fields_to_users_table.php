<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'viewer_permissions')) {
                $table->json('viewer_permissions')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('users', 'content_permissions')) {
                $table->json('content_permissions')->nullable()->after('viewer_permissions');
            }

            if (! Schema::hasColumn('users', 'content_course_ids')) {
                $table->json('content_course_ids')->nullable()->after('content_permissions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['content_course_ids', 'content_permissions', 'viewer_permissions'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
