<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('summary', 500)->nullable();
            $table->string('lesson_type', 32)->default('text');
            $table->string('video_url')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_free_preview')->default(false);
            $table->string('status', 32)->default(Course::STATUS_DRAFT)->index();
            $table->timestamps();

            $table->index(['module_id', 'sort_order']);
            $table->unique(['module_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
