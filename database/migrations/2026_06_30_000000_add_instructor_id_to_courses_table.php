<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'instructor_id')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->foreignId('instructor_id')
                    ->nullable()
                    ->after('academy_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'instructor_id')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('instructor_id');
            });
        }
    }
};
