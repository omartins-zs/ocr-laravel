<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Services\Ocr\OcrConnectionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OcrConnectionService $ocrConnectionService,
    ) {}

    public function index(): View
    {
        $snapshot = Cache::remember('dashboard.snapshot.v1', now()->addSeconds(10), function (): array {
            $statusCounts = Document::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $documentsPerDay = Document::query()
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(14))
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $confidence = DocumentExtraction::query()
                ->selectRaw('AVG(confidence) as avg_confidence, MIN(confidence) as min_confidence, MAX(confidence) as max_confidence')
                ->first();

            return [
                'status_counts' => $statusCounts,
                'documents_per_day' => $documentsPerDay,
                'confidence' => [
                    'avg_confidence' => (float) ($confidence->avg_confidence ?? 0),
                    'min_confidence' => (float) ($confidence->min_confidence ?? 0),
                    'max_confidence' => (float) ($confidence->max_confidence ?? 0),
                ],
                'queue_size' => DB::table('jobs')->where('queue', 'ocr')->count(),
                'failed_today' => DB::table('failed_jobs')->whereDate('failed_at', now())->count(),
            ];
        });

        $statusCounts = $snapshot['status_counts'];
        $summaryCards = collect(DocumentStatus::cases())
            ->reject(fn (DocumentStatus $status): bool => $status === DocumentStatus::NeedsReview)
            ->map(fn (DocumentStatus $status): array => [
                'key' => $status->value,
                'label' => $status->label(),
                'total' => (int) ($statusCounts[$status->value] ?? 0),
            ]);

        $ocrHealth = $this->ocrConnectionService->healthSnapshot();

        return view('dashboard.index', [
            'summaryCards' => $summaryCards,
            'documentsPerDay' => $snapshot['documents_per_day'],
            'queueSize' => $snapshot['queue_size'],
            'failedToday' => $snapshot['failed_today'],
            'confidence' => (object) $snapshot['confidence'],
            'ocrHealth' => $ocrHealth,
            'recentDocuments' => Document::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'uuid', 'original_filename', 'status', 'overall_confidence', 'updated_at']),
        ]);
    }
}
