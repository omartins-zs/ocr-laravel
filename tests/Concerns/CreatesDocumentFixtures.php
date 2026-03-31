<?php

namespace Tests\Concerns;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Models\ExtractedField;
use App\Models\ProcessingJob;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesDocumentFixtures
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createDocument(User $uploader, array $attributes = []): Document
    {
        return Document::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'uploaded_by' => $uploader->id,
            'storage_disk' => 'local',
            'original_path' => 'documents/tests/sample.pdf',
            'original_filename' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 2048,
            'checksum' => str_repeat('a', 64),
            'status' => DocumentStatus::Uploaded,
            'processing_stage' => ProcessingStage::Validation,
            'metadata' => [],
        ], $attributes));
    }

    protected function createProcessingJob(Document $document, ?User $creator = null, array $attributes = []): ProcessingJob
    {
        if (! $creator instanceof User) {
            $uploader = $document->uploader()->first();
            $creator = $uploader instanceof User ? $uploader : null;
        }

        return ProcessingJob::query()->create(array_merge([
            'document_id' => $document->id,
            'job_uuid' => (string) Str::uuid(),
            'queue_name' => 'ocr',
            'status' => ProcessingJobStatus::Queued,
            'stage' => ProcessingStage::Queued,
            'attempts' => 0,
            'max_attempts' => 3,
            'payload' => [
                'document_uuid' => $document->uuid,
                'is_reprocess' => false,
            ],
            'created_by' => $creator?->getKey(),
        ], $attributes));
    }

    protected function createExtraction(Document $document, array $attributes = []): DocumentExtraction
    {
        return DocumentExtraction::query()->create(array_merge([
            'document_id' => $document->id,
            'version' => 1,
            'source_engine' => 'tesseract',
            'raw_text' => 'Texto original',
            'normalized_text' => 'Texto normalizado',
            'language' => 'por',
            'confidence' => 98.5,
            'needs_review' => false,
            'extraction_payload' => [],
        ], $attributes));
    }

    protected function createExtractedField(
        Document $document,
        DocumentExtraction $extraction,
        array $attributes = [],
    ): ExtractedField {
        return ExtractedField::query()->create(array_merge([
            'document_id' => $document->id,
            'document_extraction_id' => $extraction->id,
            'field_key' => 'cpf',
            'label' => 'CPF',
            'value' => '123.456.789-10',
            'normalized_value' => '12345678910',
            'confidence' => 98.5,
            'source' => 'regex',
            'page_number' => 1,
            'is_validated' => false,
            'metadata' => [],
        ], $attributes));
    }
}
