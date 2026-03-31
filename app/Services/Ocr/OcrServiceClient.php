<?php

namespace App\Services\Ocr;

use App\Models\Document;
use App\Models\ProcessingJob;
use App\Support\PipelineLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OcrServiceClient
{
    public function __construct(
        private readonly OcrConnectionService $connectionService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function process(Document $document, ProcessingJob $processingJob): array
    {
        if (! $this->connectionService->isEnabled()) {
            throw new RuntimeException('OCR service is disabled (OCR_SERVICE_ENABLED=false).');
        }

        $baseUrl = $this->connectionService->baseUrl();

        if ($baseUrl === '') {
            throw new RuntimeException('OCR_SERVICE_URL is empty. Set a valid external OCR endpoint.');
        }

        $disk = Storage::disk($document->storage_disk);
        $filePath = null;

        try {
            $candidate = $disk->path($document->original_path);

            if (file_exists($candidate)) {
                $filePath = $candidate;
            }
        } catch (Throwable) {
            $filePath = null;
        }

        $payload = [
            'document_id' => $document->id,
            'document_uuid' => $document->uuid,
            'processing_job_uuid' => $processingJob->job_uuid,
            'file_path' => $filePath,
            'disk' => $document->storage_disk,
            'relative_path' => $document->original_path,
            'mime_type' => $document->mime_type,
            'extension' => $document->extension,
            'language' => config('ocr.default_language', 'por'),
            'enable_paddle' => config('ocr.enable_paddle', false),
        ];
        $traceContext = Arr::get($processingJob->payload, 'trace', []);
        $uploadBatchUuid = Arr::get($processingJob->payload, 'upload_batch_uuid');
        $payload['upload_batch_uuid'] = is_string($uploadBatchUuid) ? $uploadBatchUuid : null;
        $payload['trace'] = is_array($traceContext) ? $traceContext : [];

        $startedAt = microtime(true);

        try {
            $request = Http::timeout((int) config('ocr.http_timeout', 360))
                ->connectTimeout((int) config('ocr.connect_timeout', 10))
                ->retry(
                    (int) config('ocr.max_retries', 2),
                    (int) config('ocr.retry_sleep_ms', 500),
                    throw: false,
                );

            if (is_string($uploadBatchUuid) && $uploadBatchUuid !== '') {
                $request = $request->withHeaders([
                    'X-Upload-Batch-Uuid' => $uploadBatchUuid,
                ]);
            }

            $response = $request->post($this->connectionService->processUrl(), $payload);
        } catch (Throwable $exception) {
            PipelineLogger::error(
                'ocr.request_transport_failed',
                PipelineLogger::contextFor($document, $processingJob, [
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'exception_code' => $exception->getCode(),
                ]),
            );

            throw $exception;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $json = $response->json();
            $message = data_get($json, 'detail.message')
                ?? data_get($json, 'message')
                ?? 'OCR service request failed.';

            PipelineLogger::error(
                'ocr.request_http_failed',
                PipelineLogger::contextFor($document, $processingJob, [
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'duration_ms' => $durationMs,
                    'http_status' => $response->status(),
                    'response_json' => is_array($json) ? $json : null,
                    'response_body_preview' => Str::limit((string) $response->body(), 2000),
                ]),
            );

            throw new RuntimeException("OCR service returned HTTP {$response->status()}: {$message}");
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! is_array($json)) {
            PipelineLogger::error(
                'ocr.response_invalid_json',
                PipelineLogger::contextFor($document, $processingJob, [
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'duration_ms' => $durationMs,
                    'http_status' => $response->status(),
                    'response_body_preview' => Str::limit((string) $response->body(), 2000),
                ]),
            );

            throw new RuntimeException('OCR service response is invalid JSON payload.');
        }

        if (Arr::get($json, 'status') !== 'success') {
            $message = Arr::get($json, 'error.message', 'OCR service failed without explicit message.');

            PipelineLogger::error(
                'ocr.response_status_failed',
                PipelineLogger::contextFor($document, $processingJob, [
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'duration_ms' => $durationMs,
                    'response_status' => Arr::get($json, 'status'),
                    'error' => Arr::get($json, 'error'),
                ]),
            );

            throw new RuntimeException((string) $message);
        }

        return $json;
    }
}
