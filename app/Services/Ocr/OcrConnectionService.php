<?php

namespace App\Services\Ocr;

use App\Support\PipelineLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class OcrConnectionService
{
    public function isEnabled(): bool
    {
        return (bool) config('ocr.enabled', true);
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('ocr.service_url', ''), '/');
    }

    public function processUrl(): string
    {
        return $this->baseUrl().'/api/v1/process';
    }

    public function healthUrl(): string
    {
        $healthPath = '/'.ltrim((string) config('ocr.health_path', '/health'), '/');

        return $this->baseUrl().$healthPath;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthSnapshot(): array
    {
        if (app()->environment('testing')) {
            return $this->buildHealthSnapshot();
        }

        $cacheTtlSeconds = (int) config('ocr.health_cache_seconds', 15);
        if ($cacheTtlSeconds <= 0) {
            return $this->buildHealthSnapshot();
        }

        return Cache::remember(
            'ocr.health.snapshot',
            now()->addSeconds($cacheTtlSeconds),
            fn (): array => $this->buildHealthSnapshot(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHealthSnapshot(): array
    {
        $baseUrl = $this->baseUrl();
        $healthUrl = $this->healthUrl();
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: $baseUrl;

        if (! $this->isEnabled()) {
            return $this->snapshot([
                'enabled' => false,
                'reachable' => false,
                'status' => 'disabled',
                'http_status' => null,
                'host' => $host,
                'base_url' => $baseUrl,
                'health_url' => $healthUrl,
                'latency_ms' => null,
                'error' => 'OCR service disabled by configuration.',
            ]);
        }

        if ($baseUrl === '') {
            return $this->snapshot([
                'enabled' => true,
                'reachable' => false,
                'status' => 'misconfigured',
                'http_status' => null,
                'host' => 'n/a',
                'base_url' => $baseUrl,
                'health_url' => $healthUrl,
                'latency_ms' => null,
                'error' => 'OCR_SERVICE_URL is empty.',
            ]);
        }

        $started = microtime(true);
        $healthRetries = (int) config('ocr.health_retries', 0);
        $healthRetrySleepMs = max(0, (int) config('ocr.health_retry_sleep_ms', 250));

        try {
            $response = Http::timeout((int) config('ocr.health_timeout', 3))
                ->connectTimeout((int) config('ocr.health_connect_timeout', 2))
                ->retry($healthRetries, $healthRetrySleepMs)
                ->acceptJson()
                ->get($healthUrl);

            $latencyMs = (int) round((microtime(true) - $started) * 1000);
            $reachable = $response->successful();

            return $this->snapshot([
                'enabled' => true,
                'reachable' => $reachable,
                'status' => $reachable ? 'online' : 'offline',
                'http_status' => $response->status(),
                'host' => $host,
                'base_url' => $baseUrl,
                'health_url' => $healthUrl,
                'latency_ms' => $latencyMs,
                'error' => $reachable
                    ? null
                    : Str::limit((string) $response->body(), 240),
            ]);
        } catch (Throwable $exception) {
            return $this->snapshot([
                'enabled' => true,
                'reachable' => false,
                'status' => 'offline',
                'http_status' => null,
                'host' => $host,
                'base_url' => $baseUrl,
                'health_url' => $healthUrl,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function snapshot(array $snapshot): array
    {
        $this->logHealthSnapshot($snapshot);

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function logHealthSnapshot(array $snapshot): void
    {
        if (! isLocal()) {
            return;
        }

        $signature = md5(json_encode([
            'status' => $snapshot['status'] ?? null,
            'http_status' => $snapshot['http_status'] ?? null,
            'error' => $snapshot['error'] ?? null,
            'base_url' => $snapshot['base_url'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $lastSignature = null;
        try {
            $lastSignature = Cache::get('ocr.health.snapshot.last_signature');
        } catch (Throwable) {
            $lastSignature = null;
        }

        if ($lastSignature === $signature) {
            return;
        }

        try {
            Cache::put('ocr.health.snapshot.last_signature', $signature, now()->addMinutes(10));
        } catch (Throwable) {
            // Sem cache disponivel, apenas segue com o log.
        }

        PipelineLogger::info('ocr.health.snapshot', [
            'service' => 'ocr-service',
            'status' => $snapshot['status'] ?? null,
            'reachable' => (bool) ($snapshot['reachable'] ?? false),
            'http_status' => $snapshot['http_status'] ?? null,
            'latency_ms' => $snapshot['latency_ms'] ?? null,
            'host' => $snapshot['host'] ?? null,
            'base_url' => $snapshot['base_url'] ?? null,
            'health_url' => $snapshot['health_url'] ?? null,
            'error' => $snapshot['error'] ?? null,
            'message' => 'Service rodando e escutando a porta de healthcheck.',
        ]);
    }
}
