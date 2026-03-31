<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Models\Document;
use App\Models\ProcessingJob;
use App\Services\Documents\DocumentProcessingService;
use App\Support\PipelineLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 420;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $documentId,
        public readonly int $processingJobId,
    ) {
        $this->onQueue('ocr');
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'document:'.$this->documentId,
            'processing-job:'.$this->processingJobId,
        ];
    }

    public function handle(DocumentProcessingService $processingService): void
    {
        $queueJobId = $this->job?->getJobId();
        $attemptNumber = $this->attempts();

        $document = Document::query()->findOrFail($this->documentId);
        $processingJob = ProcessingJob::query()->findOrFail($this->processingJobId);
        $uploadBatchUuid = data_get($processingJob->payload, 'upload_batch_uuid');

        PipelineLogger::info(
            'job.started',
            PipelineLogger::contextFor($document, $processingJob, [
                'queue_job_id' => $queueJobId,
                'attempt' => $attemptNumber,
                'queue' => 'ocr',
                'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
            ]),
        );

        PipelineLogger::info(
            'job.models_loaded',
            PipelineLogger::contextFor($document, $processingJob, [
                'queue_job_id' => $queueJobId,
                'attempt' => $attemptNumber,
                'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
            ]),
        );

        try {
            $processingService->process($document, $processingJob);

            PipelineLogger::info(
                'job.finished',
                PipelineLogger::contextFor($document, $processingJob, [
                    'queue_job_id' => $queueJobId,
                    'attempt' => $attemptNumber,
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                ]),
            );
        } catch (Throwable $exception) {
            PipelineLogger::error(
                'job.handle_failed',
                PipelineLogger::contextFor($document, $processingJob, [
                    'queue_job_id' => $queueJobId,
                    'attempt' => $attemptNumber,
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'exception_code' => $exception->getCode(),
                ]),
            );

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if (! $exception) {
            return;
        }

        $document = Document::query()->find($this->documentId);
        $processingJob = ProcessingJob::query()->find($this->processingJobId);

        if ($document) {
            $document->update([
                'status' => DocumentStatus::Failed,
                'processing_stage' => ProcessingStage::Failed,
                'last_error' => $exception->getMessage(),
            ]);
        }

        if ($processingJob) {
            $processingJob->update([
                'status' => ProcessingJobStatus::Failed,
                'stage' => ProcessingStage::Failed,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        }

        PipelineLogger::error(
            'job.failed',
            PipelineLogger::contextFor($document, $processingJob, [
                'document_id' => $this->documentId,
                'processing_job_id' => $this->processingJobId,
                'attempt' => $this->attempts(),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
            ]),
        );
    }
}
