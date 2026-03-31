<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Models\ExtractedField;
use App\Models\ProcessingJob;
use App\Services\Extraction\FieldNormalizationService;
use App\Services\Ocr\OcrServiceClient;
use App\Support\PipelineLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentProcessingService
{
    public function __construct(
        private readonly OcrServiceClient $ocrServiceClient,
        private readonly FieldNormalizationService $normalizationService,
    ) {}

    public function process(Document $document, ProcessingJob $processingJob): void
    {
        $startedAt = now();
        $startedAtMs = (int) floor(microtime(true) * 1000);

        $document->update([
            'status' => DocumentStatus::Processing,
            'processing_stage' => ProcessingStage::Detecting,
            'last_error' => null,
        ]);

        $processingJob->update([
            'status' => ProcessingJobStatus::Processing,
            'stage' => ProcessingStage::Detecting,
            'started_at' => $startedAt,
            'attempts' => $processingJob->attempts + 1,
        ]);

        $this->log(
            $processingJob,
            $document,
            'info',
            ProcessingStage::Detecting,
            'Iniciando analise OCR.',
            [
                'event' => 'processing.started',
                'attempt_number' => $processingJob->attempts,
            ],
        );

        try {
            $this->updateStage($document, $processingJob, ProcessingStage::Ocr);
            $this->log(
                $processingJob,
                $document,
                'info',
                ProcessingStage::Ocr,
                'Chamando servico OCR Python.',
                [
                    'event' => 'processing.ocr_started',
                ],
            );

            $response = $this->ocrServiceClient->process($document, $processingJob);

            $this->updateStage($document, $processingJob, ProcessingStage::Parsing);
            $this->log(
                $processingJob,
                $document,
                'info',
                ProcessingStage::Parsing,
                'Resposta do OCR recebida. Iniciando parsing e normalizacao.',
                [
                    'event' => 'processing.ocr_response_received',
                    'engine' => Arr::get($response, 'document.engine', 'unknown'),
                    'pages_count' => count(Arr::get($response, 'pages', [])),
                    'fields_count' => count(Arr::get($response, 'fields', [])),
                    'confidence' => Arr::get($response, 'document.confidence'),
                ],
            );

            $this->updateStage($document, $processingJob, ProcessingStage::Persisting);
            $this->log(
                $processingJob,
                $document,
                'info',
                ProcessingStage::Persisting,
                'Persistindo resultado OCR no banco.',
                [
                    'event' => 'processing.persist_started',
                ],
            );

            DB::transaction(function () use ($response, $document, $processingJob, $startedAtMs): void {
                $version = ((int) $document->extractions()->max('version')) + 1;

                $document->update([
                    'status' => DocumentStatus::Approved,
                    'processing_stage' => ProcessingStage::Completed,
                    'has_native_text' => Arr::get($response, 'document.has_native_text'),
                    'total_pages' => Arr::get($response, 'document.total_pages'),
                    'overall_confidence' => Arr::get($response, 'document.confidence'),
                    'processed_at' => now(),
                    'approved_at' => now(),
                    'approved_by' => null,
                    'rejected_reason' => null,
                    'metadata' => array_merge($document->metadata ?? [], Arr::get($response, 'metadata', [])),
                ]);

                $extraction = $document->extractions()->create([
                    'version' => $version,
                    'source_engine' => Arr::get($response, 'document.engine', 'native'),
                    'raw_text' => Arr::get($response, 'text'),
                    'normalized_text' => Arr::get($response, 'normalized_text'),
                    'language' => Arr::get($response, 'document.language', 'por'),
                    'confidence' => Arr::get($response, 'document.confidence'),
                    'extraction_payload' => Arr::only($response, ['warnings', 'metadata']),
                    'needs_review' => false,
                ]);

                $document->pages()->delete();
                foreach (Arr::get($response, 'pages', []) as $page) {
                    $document->pages()->create([
                        'page_number' => Arr::get($page, 'page_number'),
                        'image_path' => Arr::get($page, 'image_path'),
                        'width' => Arr::get($page, 'width'),
                        'height' => Arr::get($page, 'height'),
                        'rotation' => Arr::get($page, 'rotation'),
                        'text_content' => Arr::get($page, 'text'),
                        'confidence' => Arr::get($page, 'confidence'),
                        'metadata' => Arr::get($page, 'metadata', []),
                    ]);
                }

                $persistedFieldsCount = $this->persistFields($document, $extraction, Arr::get($response, 'fields', []));

                $document->versions()->create([
                    'document_extraction_id' => $extraction->id,
                    'version' => $version,
                    'change_type' => 'system_processing',
                    'summary' => 'Versao criada automaticamente apos processamento OCR.',
                    'snapshot' => [
                        'confidence' => $extraction->confidence,
                        'engine' => $extraction->source_engine,
                        'fields_count' => $persistedFieldsCount,
                    ],
                ]);

                $processingJob->update([
                    'status' => ProcessingJobStatus::Completed,
                    'stage' => ProcessingStage::Completed,
                    'finished_at' => now(),
                    'duration_ms' => $this->elapsedMs($startedAtMs),
                    'ocr_engine' => Arr::get($response, 'document.engine', 'native'),
                    'result_summary' => [
                        'fields_count' => $persistedFieldsCount,
                        'pages_count' => count(Arr::get($response, 'pages', [])),
                        'confidence' => Arr::get($response, 'document.confidence'),
                        'warnings_count' => count(Arr::get($response, 'warnings', [])),
                    ],
                ]);
            });

            $this->log(
                $processingJob,
                $document,
                'info',
                ProcessingStage::Completed,
                'Processamento OCR concluido com sucesso.',
                [
                    'event' => 'processing.completed',
                    'duration_ms' => $this->elapsedMs($startedAtMs),
                    'engine' => Arr::get($response, 'document.engine', 'unknown'),
                    'confidence' => Arr::get($response, 'document.confidence'),
                    'pages_count' => count(Arr::get($response, 'pages', [])),
                    'fields_count' => count(Arr::get($response, 'fields', [])),
                ],
            );
        } catch (Throwable $exception) {
            $document->update([
                'status' => DocumentStatus::Failed,
                'processing_stage' => ProcessingStage::Failed,
                'last_error' => $exception->getMessage(),
            ]);

            $processingJob->update([
                'status' => ProcessingJobStatus::Failed,
                'stage' => ProcessingStage::Failed,
                'finished_at' => now(),
                'duration_ms' => $this->elapsedMs($startedAtMs),
                'error_code' => (string) $exception->getCode(),
                'error_message' => $exception->getMessage(),
            ]);

            $this->log(
                $processingJob,
                $document,
                'error',
                ProcessingStage::Failed,
                'Falha ao processar OCR.',
                [
                    'event' => 'processing.failed',
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'exception_code' => $exception->getCode(),
                    'duration_ms' => $this->elapsedMs($startedAtMs),
                ],
            );

            throw $exception;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function persistFields(Document $document, DocumentExtraction $extraction, array $fields): int
    {
        $persisted = 0;

        foreach ($fields as $field) {
            $key = (string) Arr::get($field, 'key', 'unknown');
            $value = Arr::get($field, 'value');

            ExtractedField::query()->create([
                'document_id' => $document->id,
                'document_extraction_id' => $extraction->id,
                'field_key' => $key,
                'label' => Arr::get($field, 'label', strtoupper($key)),
                'value' => is_scalar($value) ? (string) $value : null,
                'normalized_value' => is_scalar(Arr::get($field, 'normalized_value'))
                    ? (string) Arr::get($field, 'normalized_value')
                    : $this->normalizationService->normalize($key, is_scalar($value) ? (string) $value : null),
                'confidence' => Arr::get($field, 'confidence'),
                'source' => Arr::get($field, 'source', 'regex'),
                'page_number' => Arr::get($field, 'page_number'),
                'metadata' => Arr::get($field, 'metadata', []),
            ]);

            $persisted++;
        }

        return $persisted;
    }

    private function updateStage(Document $document, ProcessingJob $processingJob, ProcessingStage $stage): void
    {
        $document->update([
            'processing_stage' => $stage,
        ]);

        $processingJob->update([
            'stage' => $stage,
        ]);
    }

    private function elapsedMs(int $startedAtMs): int
    {
        $elapsed = (int) floor((microtime(true) * 1000) - $startedAtMs);

        return max(0, $elapsed);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(
        ProcessingJob $processingJob,
        Document $document,
        string $level,
        ProcessingStage $stage,
        string $message,
        array $context = [],
    ): void {
        $mergedContext = array_merge([
            'document_uuid' => $document->uuid,
            'processing_job_uuid' => $processingJob->job_uuid,
            'upload_batch_uuid' => Arr::get($processingJob->payload, 'upload_batch_uuid'),
            'trace' => Arr::get($processingJob->payload, 'trace', []),
        ], $context);

        $processingJob->logs()->create([
            'document_id' => $document->id,
            'level' => $level,
            'stage' => $stage->value,
            'message' => $message,
            'context' => $mergedContext,
            'logged_at' => now(),
        ]);

        $event = is_string($context['event'] ?? null) ? $context['event'] : 'processing.log';

        if ($level === 'error') {
            PipelineLogger::error($event, PipelineLogger::contextFor($document, $processingJob, [
                'stage' => $stage->value,
                'message' => $message,
                'context' => $mergedContext,
            ]));

            return;
        }

        if ($level === 'warning') {
            PipelineLogger::warning($event, PipelineLogger::contextFor($document, $processingJob, [
                'stage' => $stage->value,
                'message' => $message,
                'context' => $mergedContext,
            ]));

            return;
        }

        PipelineLogger::info($event, PipelineLogger::contextFor($document, $processingJob, [
            'stage' => $stage->value,
            'message' => $message,
            'context' => $mergedContext,
        ]));
    }
}
