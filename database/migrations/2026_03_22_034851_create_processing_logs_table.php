<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processing_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('level', 15)->default('info')->index();
            $table->string('stage', 40)->nullable()->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('logged_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_logs');
    }
};
