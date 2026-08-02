<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'price_basic')) {
                $table->decimal('price_basic', 10, 2)->nullable()->after('price_amount');
            }

            if (! Schema::hasColumn('courses', 'price_premium')) {
                $table->decimal('price_premium', 10, 2)->nullable()->after('price_basic');
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'tier')) {
                $table->string('tier', 16)->nullable()->after('purpose');
            }
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            if (! Schema::hasColumn('enrollments', 'tier')) {
                $table->string('tier', 16)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            foreach (['price_basic', 'price_premium'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'tier')) {
                $table->dropColumn('tier');
            }
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            if (Schema::hasColumn('enrollments', 'tier')) {
                $table->dropColumn('tier');
            }
        });
    }
};
