<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'offers_certificate')) {
                $table->boolean('offers_certificate')->default(false)->after('access_type');
            }
        });

        DB::table('courses')->update(['offers_certificate' => true]);

        Schema::table('courses', function (Blueprint $table): void {
            $table->string('level', 80)->nullable()->change();
            $table->string('duration', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('level', 80)->nullable(false)->change();
            $table->string('duration', 80)->nullable(false)->change();

            if (Schema::hasColumn('courses', 'offers_certificate')) {
                $table->dropColumn('offers_certificate');
            }
        });
    }
};
