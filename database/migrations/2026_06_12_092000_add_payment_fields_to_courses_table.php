<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'is_free')) {
                $table->boolean('is_free')->default(true)->after('price');
            }

            if (! Schema::hasColumn('courses', 'price_amount')) {
                $table->decimal('price_amount', 10, 2)->nullable()->after('is_free');
            }

            if (! Schema::hasColumn('courses', 'currency')) {
                $table->string('currency', 8)->default('RWF')->after('price_amount');
            }

            if (! Schema::hasColumn('courses', 'access_type')) {
                $table->string('access_type', 16)->default('free')->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            foreach (['access_type', 'currency', 'price_amount', 'is_free'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
