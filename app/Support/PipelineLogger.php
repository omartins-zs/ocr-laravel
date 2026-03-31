<?php

namespace App\Support;

use App\Models\Document;
use App\Models\ProcessingJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class PipelineLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function contextFor(?Document $document = null, ?ProcessingJob $processingJob = null, array $extra = []): array
    {
        $uploadBatchUuid = self::resolveUploadBatchUuid($processingJob, $extra);

        return array_filter(
            array_merge([
                'document_id' => $document?->id,
                'document_uuid' => $document?->uuid,
                'processing_job_id' => $processingJob?->id,
                'processing_job_uuid' => $processingJob?->job_uuid,
                'upload_batch_uuid' => $uploadBatchUuid,
            ], $extra),
            static fn (mixed $value): bool => $value !== null,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        $appTimezone = (string) config('app.timezone', 'UTC');
        $nowLocal = now($appTimezone);
        $nowUtc = now('UTC');

        $payload = array_merge([
            'event' => $event,
            'level' => $level,
            'marker' => self::markerForLevel($level),
            'timestamp' => $nowLocal->toIso8601String(),
            'timestamp_utc' => $nowUtc->toIso8601String(),
            'timezone' => $appTimezone,
        ], $context);

        try {
            $channel = config('ocr.log_channel', 'ocr_pipeline');
            Log::channel($channel)->log($level, $event, $payload);
        } catch (Throwable $exception) {
            $fallback = json_encode([
                'event' => $event,
                'level' => $level,
                'marker' => self::markerForLevel($level),
                'timestamp' => $nowLocal->toIso8601String(),
                'timestamp_utc' => $nowUtc->toIso8601String(),
                'timezone' => $appTimezone,
                'context' => $context,
                'log_exception' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (is_string($fallback)) {
                error_log($fallback);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function resolveUploadBatchUuid(?ProcessingJob $processingJob, array $extra): ?string
    {
        $batchUuidFromContext = Arr::get($extra, 'upload_batch_uuid');
        if (is_string($batchUuidFromContext) && $batchUuidFromContext !== '') {
            return $batchUuidFromContext;
        }

        $batchUuidFromPayload = Arr::get($processingJob?->payload, 'upload_batch_uuid');
        if (is_string($batchUuidFromPayload) && $batchUuidFromPayload !== '') {
            return $batchUuidFromPayload;
        }

        return null;
    }

    private static function markerForLevel(string $level): string
    {
        return match ($level) {
            'error' => 'ERROR',
            'warning' => 'WARN',
            default => 'INFO',
        };
    }
}
