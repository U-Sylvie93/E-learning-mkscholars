<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assignments', 'instruction_file_path')) {
            Schema::table('assignments', function (Blueprint $table): void {
                $table->string('instruction_file_path')->nullable()->after('instructions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assignments', 'instruction_file_path')) {
            Schema::table('assignments', function (Blueprint $table): void {
                $table->dropColumn('instruction_file_path');
            });
        }
    }
};
