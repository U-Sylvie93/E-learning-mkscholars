<?php

use App\Models\EntranceExamInstitution;
use App\Models\EntranceExamPastPaper;
use App\Models\EntranceExamProgram;
use App\Models\EntranceExamSubject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entrance_exam_institutions')) {
            Schema::create('entrance_exam_institutions', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('country', 120)->nullable();
                $table->string('status', 32)->default(EntranceExamInstitution::STATUS_ACTIVE)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('entrance_exam_programs')) {
            Schema::create('entrance_exam_programs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('entrance_exam_institution_id')->nullable()->constrained('entrance_exam_institutions')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('status', 32)->default(EntranceExamProgram::STATUS_ACTIVE)->index();
                $table->timestamps();

                $table->index(['entrance_exam_institution_id', 'status'], 'entrance_exam_programs_institution_status_idx');
            });
        }

        if (! Schema::hasTable('entrance_exam_subjects')) {
            Schema::create('entrance_exam_subjects', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('status', 32)->default(EntranceExamSubject::STATUS_ACTIVE)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('entrance_exam_past_papers')) {
            Schema::create('entrance_exam_past_papers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('entrance_exam_institution_id')->nullable()->constrained('entrance_exam_institutions')->nullOnDelete();
                $table->foreignId('entrance_exam_program_id')->nullable()->constrained('entrance_exam_programs')->nullOnDelete();
                $table->foreignId('entrance_exam_subject_id')->nullable()->constrained('entrance_exam_subjects')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('exam_year')->nullable()->index();
                $table->string('exam_type', 120)->nullable()->index();
                $table->string('paper_file_path');
                $table->string('paper_file_disk', 32)->default('public');
                $table->string('paper_file_mime', 120)->nullable();
                $table->unsignedInteger('page_count')->nullable();
                $table->boolean('is_featured')->default(false)->index();
                $table->string('status', 32)->default(EntranceExamPastPaper::STATUS_DRAFT)->index();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['entrance_exam_institution_id', 'status'], 'entrance_exam_papers_institution_status_idx');
                $table->index(['entrance_exam_program_id', 'status'], 'entrance_exam_papers_program_status_idx');
                $table->index(['entrance_exam_subject_id', 'status'], 'entrance_exam_papers_subject_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entrance_exam_past_papers');
        Schema::dropIfExists('entrance_exam_subjects');
        Schema::dropIfExists('entrance_exam_programs');
        Schema::dropIfExists('entrance_exam_institutions');
    }
};
