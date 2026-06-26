<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_skills');
    }
};
