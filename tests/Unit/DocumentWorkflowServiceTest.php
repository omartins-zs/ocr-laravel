<?php

namespace Tests\Unit;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Jobs\ProcessDocumentJob;
use App\Services\Documents\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class DocumentWorkflowServiceTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_it_dispatches_document_to_queue_and_creates_processing_job(): void
    {
        Queue::fake();

        $actor = $this->createUser();
        $document = $this->createDocument($actor, [
            'status' => DocumentStatus::Uploaded,
            'processing_stage' => ProcessingStage::Validation,
            'last_error' => 'erro antigo',
            'rejected_reason' => 'rejeitado antes',
        ]);

        $service = app(DocumentWorkflowService::class);
        $processingJob = $service->dispatchProcessing($document, $actor);

        $document->refresh();

        $this->assertSame(DocumentStatus::Queued, $document->status);
        $this->assertSame(ProcessingStage::Queued, $document->processing_stage);
        $this->assertNull($document->last_error);
        $this->assertNull($document->rejected_reason);

        $this->assertSame(ProcessingJobStatus::Queued, $processingJob->status);
        $this->assertSame(ProcessingStage::Queued, $processingJob->stage);
        $this->assertSame(0, $processingJob->attempts);
        $this->assertSame(3, $processingJob->max_attempts);
        $this->assertSame($actor->id, $processingJob->created_by);

        $this->assertDatabaseCount('processing_logs', 2);

        Queue::assertPushedOn('ocr', ProcessDocumentJob::class);
        Queue::assertPushed(ProcessDocumentJob::class, function (ProcessDocumentJob $job) use ($document, $processingJob): bool {
            return $job->documentId === $document->id
                && $job->processingJobId === $processingJob->id;
        });
    }
}
