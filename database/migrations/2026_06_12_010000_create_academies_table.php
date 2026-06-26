<?php

use App\Models\Academy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('status', 32)->default(Academy::STATUS_DRAFT)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academies');
    }
};
