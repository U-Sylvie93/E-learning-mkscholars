<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn(['feedback', 'graded_at']);
        });
    }
};
