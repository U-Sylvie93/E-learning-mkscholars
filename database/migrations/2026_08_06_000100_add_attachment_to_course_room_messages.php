<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('course_room_messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('body');
            }
            if (! Schema::hasColumn('course_room_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }
            if (! Schema::hasColumn('course_room_messages', 'attachment_mime')) {
                $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
            }
            if (! Schema::hasColumn('course_room_messages', 'attachment_size')) {
                $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });

        // Allow the message body to be optional when an attachment is provided.
        Schema::table('course_room_messages', function (Blueprint $table): void {
            $table->text('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_room_messages', function (Blueprint $table): void {
            foreach (['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'] as $column) {
                if (Schema::hasColumn('course_room_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
