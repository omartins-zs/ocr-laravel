<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracted_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_extraction_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 80)->index();
            $table->string('label', 120);
            $table->text('value')->nullable();
            $table->text('normalized_value')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('source', 30)->default('regex');
            $table->unsignedInteger('page_number')->nullable();
            $table->boolean('is_validated')->default(false)->index();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracted_fields');
    }
};
