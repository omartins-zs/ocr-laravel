<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingStage;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_api_documents_endpoint(): void
    {
        $response = $this->getJson('/api/v1/documents');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_upload_document_from_api(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $file = UploadedFile::fake()->create('api-documento.pdf', 80, 'application/pdf');

        $response = $this
            ->actingAs($user)
            ->post('/api/v1/documents', [
                'metadata' => json_encode(['origem' => 'api-test']),
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $document = Document::query()->firstOrFail();

        $response->assertCreated()
            ->assertJsonPath('data.uuid', $document->uuid)
            ->assertJsonPath('data.filename', 'api-documento.pdf')
            ->assertJsonPath('data.status', DocumentStatus::Queued->value);

        Queue::assertPushedOn('ocr', ProcessDocumentJob::class);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => DocumentStatus::Queued->value,
            'processing_stage' => ProcessingStage::Queued->value,
        ]);
    }

    public function test_status_endpoint_returns_processing_information(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument($user, [
            'status' => DocumentStatus::Processing,
            'processing_stage' => ProcessingStage::Ocr,
        ]);
        $processingJob = $this->createProcessingJob($document, $user, [
            'status' => 'processing',
            'stage' => 'ocr',
            'attempts' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.documents.status', $document));

        $response->assertOk()
            ->assertJsonPath('document_uuid', $document->uuid)
            ->assertJsonPath('status', DocumentStatus::Processing->value)
            ->assertJsonPath('stage', ProcessingStage::Ocr->value)
            ->assertJsonPath('processing_job.job_uuid', $processingJob->job_uuid)
            ->assertJsonPath('processing_job.attempts', 2);
    }

    public function test_fields_endpoint_returns_not_found_when_document_has_no_extraction(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument($user);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.documents.fields', $document));

        $response->assertNotFound()
            ->assertJsonPath('message', 'Documento ainda nao possui extracao.');
    }

    public function test_fields_endpoint_returns_latest_extraction_with_fields(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument($user);
        $extraction = $this->createExtraction($document, [
            'version' => 2,
        ]);
        $this->createExtractedField($document, $extraction, [
            'field_key' => 'cnpj',
            'label' => 'CNPJ',
            'value' => '12.345.678/0001-90',
            'normalized_value' => '12345678000190',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.documents.fields', $document));

        $response->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.fields.0.key', 'cnpj')
            ->assertJsonPath('data.fields.0.normalized_value', '12345678000190');
    }

    public function test_api_batch_upload_processes_valid_files_and_reports_invalid_entries(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $validPdf = UploadedFile::fake()->create('api-lote-1.pdf', 120, 'application/pdf');
        $validJpg = UploadedFile::fake()->create('api-lote-2.jpg', 90, 'image/jpeg');
        $invalidTxt = UploadedFile::fake()->create('api-lote-3.txt', 5, 'text/plain');

        $response = $this
            ->actingAs($user)
            ->post('/api/v1/documents', [
                'files' => [$validPdf, $validJpg, $invalidTxt],
                'metadata' => json_encode(['origem' => 'api-lote-misto']),
            ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('invalid_files_count', 1)
            ->assertJsonPath('invalid_files.0.name', 'api-lote-3.txt')
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseCount('processing_jobs', 2);
        Queue::assertPushed(ProcessDocumentJob::class, 2);
    }
}
