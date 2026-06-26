<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            if (! Schema::hasColumn('certificates', 'signer_name')) {
                $table->string('signer_name')->nullable()->after('score');
            }

            if (! Schema::hasColumn('certificates', 'signer_title')) {
                $table->string('signer_title')->nullable()->after('signer_name');
            }

            if (! Schema::hasColumn('certificates', 'signature_image_path')) {
                $table->string('signature_image_path')->nullable()->after('signer_title');
            }
        });
    }

    public function down(): void
    {
        foreach (['signature_image_path', 'signer_title', 'signer_name'] as $column) {
            if (Schema::hasColumn('certificates', $column)) {
                Schema::table('certificates', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
