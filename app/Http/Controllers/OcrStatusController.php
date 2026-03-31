<?php

namespace App\Http\Controllers;

use App\Services\Ocr\OcrConnectionService;
use Illuminate\Http\JsonResponse;

class OcrStatusController extends Controller
{
    public function __construct(
        private readonly OcrConnectionService $ocrConnectionService,
    ) {}

    public function show(): JsonResponse
    {
        $snapshot = $this->ocrConnectionService->healthSnapshot();
        $state = (string) ($snapshot['status'] ?? 'offline');

        if ($state === 'misconfigured') {
            $state = 'offline';
        }

        $label = match ($state) {
            'online' => 'online',
            'disabled' => 'desligado',
            default => 'offline',
        };

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'OCR status fetched',
            'data' => [
                'state' => $state,
                'label' => $label,
                'enabled' => (bool) ($snapshot['enabled'] ?? false),
                'reachable' => (bool) ($snapshot['reachable'] ?? false),
                'http_status' => $snapshot['http_status'] ?? null,
                'latency_ms' => $snapshot['latency_ms'] ?? null,
                'base_url' => $snapshot['base_url'] ?? null,
                'health_url' => $snapshot['health_url'] ?? null,
                'host' => $snapshot['host'] ?? null,
                'error' => $snapshot['error'] ?? null,
                'checked_at' => now()->toIso8601String(),
            ],
            'errors' => [],
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
