<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_application_id')->constrained()->cascadeOnDelete();
            $table->string('document_name');
            $table->string('file_path')->nullable();
            $table->string('external_link')->nullable();
            $table->string('status', 32)->default('pending');
            $table->text('admin_feedback')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
