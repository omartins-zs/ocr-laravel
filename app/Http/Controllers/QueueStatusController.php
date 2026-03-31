<?php

namespace App\Http\Controllers;

use App\Enums\ProcessingJobStatus;
use App\Models\ProcessingJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueueStatusController extends Controller
{
    public function index(): View
    {
        $snapshot = Cache::remember('queue.status.snapshot.v1', now()->addSeconds(10), function (): array {
            $statusTotals = ProcessingJob::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $chart = collect(ProcessingJobStatus::cases())->map(fn (ProcessingJobStatus $status): array => [
                'status' => $status->value,
                'total' => (int) ($statusTotals[$status->value] ?? 0),
            ]);

            return [
                'queue_depth' => DB::table('jobs')->where('queue', 'ocr')->count(),
                'failed_queue' => DB::table('failed_jobs')->count(),
                'status_totals' => $statusTotals,
                'chart' => $chart,
            ];
        });

        return view('queue.index', [
            'queueDepth' => $snapshot['queue_depth'],
            'failedQueue' => $snapshot['failed_queue'],
            'statusTotals' => $snapshot['status_totals'],
            'chart' => $snapshot['chart'],
            'recentJobs' => ProcessingJob::query()
                ->with('document:id,original_filename,uuid')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
