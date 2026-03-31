<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table): void {
                if (Schema::hasColumn('documents', 'document_type_id')) {
                    $table->dropConstrainedForeignId('document_type_id');
                }

                if (Schema::hasColumn('documents', 'assigned_to')) {
                    $table->dropConstrainedForeignId('assigned_to');
                }
            });
        }

        Schema::dropIfExists('document_types');
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->json('field_schema')->nullable();
                $table->json('parsing_rules')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table): void {
                if (! Schema::hasColumn('documents', 'document_type_id')) {
                    $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
                }

                if (! Schema::hasColumn('documents', 'assigned_to')) {
                    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }
    }
};
