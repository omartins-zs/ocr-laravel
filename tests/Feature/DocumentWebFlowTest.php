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

class DocumentWebFlowTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_document_from_web(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $file = UploadedFile::fake()->create('nota-fiscal.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'metadata' => ['origem' => 'teste'],
            'file' => $file,
        ]);

        $document = Document::query()->firstOrFail();

        $response->assertRedirect(route('documents.show', $document));
        $response->assertSessionHas('success');

        $this->assertSame('nota-fiscal.pdf', $document->original_filename);
        $this->assertSame(DocumentStatus::Queued, $document->status);
        $this->assertSame(ProcessingStage::Queued, $document->processing_stage);

        Queue::assertPushedOn('ocr', ProcessDocumentJob::class);
        $this->assertDatabaseHas('processing_jobs', [
            'document_id' => $document->id,
            'queue_name' => 'ocr',
            'status' => 'queued',
        ]);
    }

    public function test_upload_validation_rejects_invalid_file_type(): void
    {
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $file = UploadedFile::fake()->create('arquivo.txt', 5, 'text/plain');

        $response = $this->actingAs($user)->from(route('documents.create'))->post(route('documents.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('documents.create'));
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_reprocess_creates_new_processing_job_for_failed_document(): void
    {
        Queue::fake();

        $user = $this->createUser();
        $document = $this->createDocument($user, [
            'status' => DocumentStatus::Failed,
            'processing_stage' => ProcessingStage::Failed,
            'last_error' => 'Falha anterior',
        ]);

        $response = $this->actingAs($user)->post(route('documents.reprocess', $document));

        $response->assertRedirect(route('documents.show', $document));
        $response->assertSessionHas('success');

        $document->refresh();

        $this->assertSame(DocumentStatus::Queued, $document->status);
        $this->assertSame(ProcessingStage::Queued, $document->processing_stage);
        $this->assertNull($document->last_error);

        $this->assertDatabaseCount('processing_jobs', 1);
        Queue::assertPushedOn('ocr', ProcessDocumentJob::class);
    }

    public function test_authenticated_user_can_upload_multiple_documents_in_a_single_request(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $fileA = UploadedFile::fake()->create('documento-a.pdf', 120, 'application/pdf');
        $fileB = UploadedFile::fake()->create('documento-b.jpg', 90, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'files' => [$fileA, $fileB],
            'metadata' => ['origem' => 'lote-teste'],
        ]);

        $response->assertRedirect(route('history'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseCount('processing_jobs', 2);
        Queue::assertPushed(ProcessDocumentJob::class, 2);
    }

    public function test_batch_upload_processes_valid_files_and_skips_invalid_ones(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['ocr.storage_disk' => 'local']);

        $user = $this->createUser();
        $validPdf = UploadedFile::fake()->create('lote-1.pdf', 120, 'application/pdf');
        $validJpg = UploadedFile::fake()->create('lote-2.jpg', 90, 'image/jpeg');
        $invalidTxt = UploadedFile::fake()->create('lote-3.txt', 5, 'text/plain');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'files' => [$validPdf, $validJpg, $invalidTxt],
            'metadata' => ['origem' => 'lote-misto'],
        ]);

        $response->assertRedirect(route('history'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseCount('processing_jobs', 2);
        Queue::assertPushed(ProcessDocumentJob::class, 2);
    }
}
