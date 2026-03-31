<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\ProcessingJob;
use App\Models\User;
use App\Support\PipelineLogger;

class DocumentWorkflowService
{
    /**
     * @param  array<string, mixed>  $traceContext
     */
    public function dispatchProcessing(
        Document $document,
        User $actor,
        bool $isReprocess = false,
        array $traceContext = [],
    ): ProcessingJob {
        $traceContext = $this->sanitizeTraceContext($traceContext);
        $uploadBatchUuid = is_string($traceContext['upload_batch_uuid'] ?? null)
            ? $traceContext['upload_batch_uuid']
            : null;

        PipelineLogger::info(
            'queue.dispatching',
            PipelineLogger::contextFor($document, null, [
                'is_reprocess' => $isReprocess,
                'actor_user_id' => $actor->id,
                'upload_batch_uuid' => $uploadBatchUuid,
                'queue_name' => 'ocr',
                'queue_connection' => config('queue.default'),
                'trace' => $traceContext,
            ]),
        );

        $document->update([
            'status' => DocumentStatus::Queued,
            'processing_stage' => ProcessingStage::Queued,
            'last_error' => null,
            'rejected_reason' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $processingJob = $document->processingJobs()->create([
            'queue_name' => 'ocr',
            'status' => ProcessingJobStatus::Queued,
            'stage' => ProcessingStage::Queued,
            'attempts' => 0,
            'max_attempts' => 3,
            'payload' => [
                'document_uuid' => $document->uuid,
                'is_reprocess' => $isReprocess,
                'upload_batch_uuid' => $uploadBatchUuid,
                'trace' => $traceContext,
            ],
            'created_by' => $actor->id,
        ]);

        $this->writeProcessingLog(
            $processingJob,
            $document,
            'info',
            ProcessingStage::Queued,
            $isReprocess
                ? 'Documento enviado para reprocessamento na fila OCR.'
                : 'Documento enviado para processamento na fila OCR.',
            [
                'event' => 'queue.dispatching',
                'document_uuid' => $document->uuid,
                'queued_by' => $actor->id,
                'upload_batch_uuid' => $uploadBatchUuid,
                'queue_name' => $processingJob->queue_name,
                'is_reprocess' => $isReprocess,
                'trace' => $traceContext,
            ],
        );

        ProcessDocumentJob::dispatch($document->id, $processingJob->id)
            ->onQueue('ocr')
            ->afterCommit();

        $this->writeProcessingLog(
            $processingJob,
            $document,
            'info',
            ProcessingStage::Queued,
            'Job de OCR despachado com sucesso para a fila.',
            [
                'event' => 'queue.dispatched',
                'document_uuid' => $document->uuid,
                'processing_job_uuid' => $processingJob->job_uuid,
                'upload_batch_uuid' => $uploadBatchUuid,
                'queue_name' => $processingJob->queue_name,
                'trace' => $traceContext,
            ],
        );

        PipelineLogger::info(
            'queue.dispatched',
            PipelineLogger::contextFor($document, $processingJob, [
                'is_reprocess' => $isReprocess,
                'actor_user_id' => $actor->id,
                'upload_batch_uuid' => $uploadBatchUuid,
                'queue_name' => $processingJob->queue_name,
                'queue_connection' => config('queue.default'),
                'trace' => $traceContext,
            ]),
        );

        return $processingJob;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeProcessingLog(
        ProcessingJob $processingJob,
        Document $document,
        string $level,
        ProcessingStage $stage,
        string $message,
        array $context = [],
    ): void {
        $processingJob->logs()->create([
            'document_id' => $document->id,
            'level' => $level,
            'stage' => $stage->value,
            'message' => $message,
            'context' => $context,
            'logged_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $traceContext
     * @return array<string, mixed>
     */
    private function sanitizeTraceContext(array $traceContext): array
    {
        return array_filter(
            $traceContext,
            static fn (mixed $value): bool => is_scalar($value) && $value !== '',
        );
    }
}
