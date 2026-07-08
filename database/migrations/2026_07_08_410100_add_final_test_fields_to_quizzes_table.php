<?php

use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            if (! Schema::hasColumn('quizzes', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('lesson_id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('quizzes', 'quiz_type')) {
                $table->string('quiz_type', 32)->default(Quiz::TYPE_LESSON_QUIZ)->after('course_id')->index();
            }
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->unsignedBigInteger('lesson_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            if (Schema::hasColumn('quizzes', 'quiz_type')) {
                $table->dropColumn('quiz_type');
            }

            if (Schema::hasColumn('quizzes', 'course_id')) {
                $table->dropConstrainedForeignId('course_id');
            }
        });
    }
};
