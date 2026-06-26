<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 600);
            $table->text('full_description')->nullable();
            $table->string('level', 80);
            $table->string('duration', 80);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status', 32)->default(Course::STATUS_DRAFT)->index();
            $table->string('featured_image_path')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
