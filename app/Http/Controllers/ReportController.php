<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentExtraction;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $processedByDay = Document::query()
            ->selectRaw('DATE(processed_at) as day, COUNT(*) as total')
            ->whereNotNull('processed_at')
            ->where('processed_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $statusDistribution = Document::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $averageConfidenceByType = DocumentExtraction::query()
            ->selectRaw('source_engine as engine_name, AVG(confidence) as avg_confidence')
            ->whereNotNull('source_engine')
            ->groupBy('source_engine')
            ->orderBy('engine_name')
            ->get();

        return view('reports.index', [
            'processedByDay' => $processedByDay,
            'statusDistribution' => $statusDistribution,
            'averageConfidenceByType' => $averageConfidenceByType,
        ]);
    }
}
