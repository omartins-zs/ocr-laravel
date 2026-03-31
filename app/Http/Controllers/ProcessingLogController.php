<?php

namespace App\Http\Controllers;

use App\Models\ProcessingLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProcessingLogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'level' => ['nullable', 'string', 'max:20'],
            'stage' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $logs = ProcessingLog::query()
            ->select(['id', 'document_id', 'processing_job_id', 'level', 'stage', 'message', 'logged_at'])
            ->with(['document:id,uuid,original_filename', 'processingJob:id,job_uuid'])
            ->when(
                $request->filled('level'),
                fn ($query) => $query->where('level', $request->string('level')->toString()),
            )
            ->when(
                $request->filled('stage'),
                fn ($query) => $query->where('stage', $request->string('stage')->toString()),
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where('message', 'like', "%{$term}%");
            })
            ->latest('logged_at')
            ->paginate(30)
            ->withQueryString();

        $levels = Cache::remember('processing-logs.levels.v1', now()->addMinutes(2), fn () => ProcessingLog::query()
            ->distinct('level')
            ->orderBy('level')
            ->pluck('level'));
        $stages = Cache::remember('processing-logs.stages.v1', now()->addMinutes(2), fn () => ProcessingLog::query()
            ->distinct('stage')
            ->whereNotNull('stage')
            ->orderBy('stage')
            ->pluck('stage'));

        return view('processing-logs.index', [
            'logs' => $logs,
            'levels' => $levels,
            'stages' => $stages,
            'filters' => $request->only(['level', 'stage', 'q']),
        ]);
    }
}
