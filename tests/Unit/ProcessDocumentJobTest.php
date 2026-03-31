<?php

namespace Tests\Unit;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_it_has_expected_retry_and_timeout_configuration(): void
    {
        $job = new ProcessDocumentJob(1, 1);

        $this->assertSame(3, $job->tries);
        $this->assertSame(420, $job->timeout);
        $this->assertSame([30, 120, 300], $job->backoff);
    }

    public function test_it_returns_consistent_tags(): void
    {
        $job = new ProcessDocumentJob(12, 34);

        $this->assertSame(['document:12', 'processing-job:34'], $job->tags());
    }

    public function test_failed_marks_document_and_processing_job_as_failed(): void
    {
        $actor = $this->createUser();
        $document = $this->createDocument($actor, [
            'status' => DocumentStatus::Processing,
            'processing_stage' => ProcessingStage::Ocr,
        ]);
        $processingJob = $this->createProcessingJob($document, $actor, [
            'status' => ProcessingJobStatus::Processing,
            'stage' => ProcessingStage::Ocr,
        ]);

        $job = new ProcessDocumentJob($document->id, $processingJob->id);
        $job->failed(new RuntimeException('Falha de OCR para teste'));

        $document->refresh();
        $processingJob->refresh();

        $this->assertSame(DocumentStatus::Failed, $document->status);
        $this->assertSame(ProcessingStage::Failed, $document->processing_stage);
        $this->assertSame('Falha de OCR para teste', $document->last_error);

        $this->assertSame(ProcessingJobStatus::Failed, $processingJob->status);
        $this->assertSame(ProcessingStage::Failed, $processingJob->stage);
        $this->assertSame('Falha de OCR para teste', $processingJob->error_message);
        $this->assertNotNull($processingJob->finished_at);
    }
}
