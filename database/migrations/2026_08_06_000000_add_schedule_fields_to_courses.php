<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'start_date')) {
                $table->date('start_date')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('courses', 'registration_deadline')) {
                $table->date('registration_deadline')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('courses', 'available_seats')) {
                $table->unsignedInteger('available_seats')->nullable()->after('registration_deadline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            foreach (['start_date', 'registration_deadline', 'available_seats'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
