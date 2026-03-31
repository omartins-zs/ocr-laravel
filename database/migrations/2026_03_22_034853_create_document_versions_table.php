<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_extraction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('change_type', 40)->default('system_processing');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
