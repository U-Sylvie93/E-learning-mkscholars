<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('course_room_messages', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('edited_at');
            }
            if (! Schema::hasColumn('course_room_messages', 'pinned_by_id')) {
                $table->foreignId('pinned_by_id')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('course_room_messages', 'pinned_by_id')) {
                $table->dropConstrainedForeignId('pinned_by_id');
            }
            if (Schema::hasColumn('course_room_messages', 'pinned_at')) {
                $table->dropColumn('pinned_at');
            }
        });
    }
};
