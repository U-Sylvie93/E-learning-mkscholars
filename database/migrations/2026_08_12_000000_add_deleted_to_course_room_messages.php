<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('course_room_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('attachment_size');
            }
            if (! Schema::hasColumn('course_room_messages', 'deleted_by_id')) {
                $table->foreignId('deleted_by_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('course_room_messages', 'deleted_by_id')) {
                $table->dropConstrainedForeignId('deleted_by_id');
            }
            if (Schema::hasColumn('course_room_messages', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
