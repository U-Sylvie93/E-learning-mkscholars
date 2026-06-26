<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table): void {
            if (! Schema::hasColumn('application_documents', 'student_document_id')) {
                $table->foreignId('student_document_id')
                    ->nullable()
                    ->after('student_application_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table): void {
            if (Schema::hasColumn('application_documents', 'student_document_id')) {
                $table->dropConstrainedForeignId('student_document_id');
            }
        });
    }
};
