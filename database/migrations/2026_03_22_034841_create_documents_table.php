<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('storage_disk', 50)->default('local');
            $table->string('original_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('total_pages')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->string('status', 40)->default('uploaded')->index();
            $table->string('processing_stage', 40)->default('uploaded')->index();
            $table->boolean('has_native_text')->nullable();
            $table->decimal('overall_confidence', 5, 2)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['uploaded_by', 'created_at']);
            $table->index(['status', 'processing_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
