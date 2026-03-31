<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('source_engine', 40)->default('native');
            $table->longText('raw_text')->nullable();
            $table->longText('normalized_text')->nullable();
            $table->string('language', 20)->default('por');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('extraction_payload')->nullable();
            $table->boolean('needs_review')->default(true)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extractions');
    }
};
