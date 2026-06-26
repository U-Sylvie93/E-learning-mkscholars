<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('summary', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 32)->default(Course::STATUS_DRAFT)->index();
            $table->timestamps();

            $table->index(['course_id', 'sort_order']);
            $table->unique(['course_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
