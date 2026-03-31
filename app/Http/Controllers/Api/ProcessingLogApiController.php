<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcessingLogResource;
use App\Models\ProcessingLog;
use Illuminate\Http\Request;

class ProcessingLogApiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'document_uuid' => ['nullable', 'exists:documents,uuid'],
            'processing_job_uuid' => ['nullable', 'exists:processing_jobs,job_uuid'],
            'level' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'between:10,100'],
        ]);

        $logs = ProcessingLog::query()
            ->with(['document', 'processingJob'])
            ->when($request->filled('document_uuid'), function ($query) use ($request): void {
                $query->whereHas('document', fn ($inner) => $inner->where('uuid', $request->string('document_uuid')));
            })
            ->when($request->filled('processing_job_uuid'), function ($query) use ($request): void {
                $query->whereHas('processingJob', fn ($inner) => $inner->where('job_uuid', $request->string('processing_job_uuid')));
            })
            ->when(
                $request->filled('level'),
                fn ($query) => $query->where('level', $request->string('level')->toString()),
            )
            ->latest('logged_at')
            ->paginate($request->integer('per_page', 30));

        return ProcessingLogResource::collection($logs);
    }
}
