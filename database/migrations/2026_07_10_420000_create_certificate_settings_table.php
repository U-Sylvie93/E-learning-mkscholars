<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_name')->default('MK Scholars');
            $table->string('logo_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('admin_signature_path')->nullable();
            $table->string('issuer_name')->nullable();
            $table->string('issuer_title')->nullable();
            $table->text('certificate_footer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_settings');
    }
};
