<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            if (! Schema::hasColumn('lesson_progress', 'course_id')) {
                $table->foreignId('course_id')
                    ->after('user_id')
                    ->nullable()
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
