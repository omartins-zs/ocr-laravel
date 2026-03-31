<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\Documents\DocumentIngestionService;
use App\Services\Documents\DocumentWorkflowService;
use App\Support\PipelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Document::class);

        $statusOptions = collect(DocumentStatus::cases())
            ->reject(fn (DocumentStatus $status): bool => $status === DocumentStatus::NeedsReview)
            ->values();
        $statusValues = $statusOptions->map->value->all();
        $request->validate([
            'status' => ['nullable', Rule::in($statusValues)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $documents = Document::query()
            ->search($request->string('q')->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statusCounters = Document::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as processing', [
                DocumentStatus::Uploaded->value,
                DocumentStatus::Queued->value,
                DocumentStatus::Processing->value,
            ])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [DocumentStatus::Approved->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [DocumentStatus::Failed->value])
            ->first();

        $summary = [
            'total' => (int) ($statusCounters->total ?? 0),
            'processing' => (int) ($statusCounters->processing ?? 0),
            'approved' => (int) ($statusCounters->approved ?? 0),
            'failed' => (int) ($statusCounters->failed ?? 0),
        ];

        $groupedDocuments = $documents->getCollection()
            ->groupBy(function (Document $document): string {
                $batchUuid = data_get($document->metadata, 'upload_batch_uuid');
                if (is_string($batchUuid) && $batchUuid !== '') {
                    return 'batch:'.$batchUuid;
                }

                return 'single:'.$document->id;
            });

        $batchGroups = $groupedDocuments
            ->filter(fn (Collection $group, string $key): bool => str_starts_with($key, 'batch:'))
            ->map(function (Collection $group, string $key): array {
                $first = $group->first();
                $statusCounts = $group
                    ->map(fn (Document $document): string => $document->status->value)
                    ->countBy()
                    ->all();
                $createdAt = $group
                    ->sortBy(fn (Document $document): int => $document->created_at?->getTimestamp() ?? 0)
                    ->first()?->created_at;
                $updatedAt = $group
                    ->sortByDesc(fn (Document $document): int => $document->updated_at?->getTimestamp() ?? 0)
                    ->first()?->updated_at;

                return [
                    'key' => str_replace(':', '-', $key),
                    'batch_uuid' => data_get($first?->metadata, 'upload_batch_uuid'),
                    'documents' => $group->values(),
                    'files_count' => $group->count(),
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'status_counts' => $statusCounts,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        $singleDocuments = $groupedDocuments
            ->filter(fn (Collection $group, string $key): bool => str_starts_with($key, 'single:'))
            ->map(fn (Collection $group): ?Document => $group->first())
            ->filter()
            ->sortByDesc(fn (Document $document): int => $document->updated_at?->getTimestamp() ?? 0)
            ->values();

        return view('documents.index', [
            'documents' => $documents,
            'batchGroups' => $batchGroups,
            'singleDocuments' => $singleDocuments,
            'statusOptions' => $statusOptions->all(),
            'filters' => $request->only(['q', 'status']),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Document::class);

        return view('documents.create');
    }

    public function store(StoreDocumentRequest $request, DocumentIngestionService $ingestionService): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $requestStartedAtMs = (int) floor(microtime(true) * 1000);
        $resolvedFiles = $this->resolveUploadFiles($request);
        ['valid' => $files, 'invalid' => $invalidFiles] = $this->partitionFilesForProcessing($resolvedFiles);
        $validated = $request->validated();
        $metadata = Arr::get($validated, 'metadata', []);
        $filesCount = $files->count();
        $uploadBatchUuid = $filesCount > 1 ? (string) Str::uuid() : null;

        PipelineLogger::info('upload.controller.entered', [
            'source' => 'web',
            'controller' => self::class,
            'action' => 'store',
            'upload_batch_uuid' => $uploadBatchUuid,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'route' => $request->path(),
            'method' => $request->method(),
            'resolved_files_count' => $resolvedFiles->count(),
            'files_count' => $filesCount,
            'invalid_files_count' => count($invalidFiles),
        ]);

        if ($files->isEmpty()) {
            PipelineLogger::warning('upload.controller.no_valid_files', [
                'source' => 'web',
                'controller' => self::class,
                'action' => 'store',
                'upload_batch_uuid' => $uploadBatchUuid,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'resolved_files_count' => $resolvedFiles->count(),
                'invalid_files_count' => count($invalidFiles),
                'invalid_files' => $invalidFiles,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'files' => 'Nenhum arquivo valido encontrado. Envie PDF, PNG, JPG ou JPEG com ate 50MB.',
                ]);
        }

        PipelineLogger::info('upload.batch_received', [
            'source' => 'web',
            'route' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'controller' => self::class,
            'action' => 'store',
            'upload_batch_uuid' => $uploadBatchUuid,
            'resolved_files_count' => $resolvedFiles->count(),
            'files_count' => $filesCount,
            'invalid_files_count' => count($invalidFiles),
            'invalid_files' => $invalidFiles,
            'payload' => Arr::except($validated, ['file', 'files']),
        ]);

        $documents = [];

        foreach ($files->values() as $index => $file) {
            $fileIndex = $index + 1;
            $traceContext = [
                'upload_batch_uuid' => $uploadBatchUuid,
                'source' => 'web',
                'controller' => self::class,
                'action' => 'store',
                'route' => $request->path(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'file_index' => $fileIndex,
                'files_total' => $filesCount,
            ];

            PipelineLogger::info('upload.request_received', [
                'source' => 'web',
                'route' => $request->path(),
                'method' => $request->method(),
                'controller' => self::class,
                'action' => 'store',
                'upload_batch_uuid' => $uploadBatchUuid,
                'file_index' => $fileIndex,
                'files_total' => $filesCount,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'payload' => Arr::except($validated, ['file', 'files']),
                'file' => [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => strtolower($file->getClientOriginalExtension() ?: ''),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                ],
            ]);

            try {
                $document = $ingestionService->ingest(
                    $file,
                    $request->user(),
                    [
                        'metadata' => $metadata,
                        'trace' => $traceContext,
                    ],
                );
            } catch (Throwable $exception) {
                PipelineLogger::error('upload.ingestion_failed', [
                    'source' => 'web',
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'controller' => self::class,
                    'action' => 'store',
                    'upload_batch_uuid' => $uploadBatchUuid,
                    'file_index' => $fileIndex,
                    'files_total' => $filesCount,
                    'user_id' => $request->user()?->id,
                    'file' => [
                        'original_name' => $file->getClientOriginalName(),
                        'extension' => strtolower($file->getClientOriginalExtension() ?: ''),
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ],
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'exception_code' => $exception->getCode(),
                ]);

                throw $exception;
            }

            $documents[] = $document;

            $latestProcessingJob = $document->latestProcessingJob()->first();

            PipelineLogger::info(
                'upload.ingestion_completed',
                PipelineLogger::contextFor($document, $latestProcessingJob, [
                    'upload_batch_uuid' => $uploadBatchUuid,
                    'file_index' => $fileIndex,
                    'files_total' => $filesCount,
                    'status' => $document->status->value,
                    'processing_stage' => $document->processing_stage->value,
                    'storage_disk' => $document->storage_disk,
                    'original_path' => $document->original_path,
                ]),
            );
        }

        PipelineLogger::info('upload.controller.completed', [
            'source' => 'web',
            'controller' => self::class,
            'action' => 'store',
            'upload_batch_uuid' => $uploadBatchUuid,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'documents_created' => count($documents),
            'invalid_files_count' => count($invalidFiles),
            'duration_ms' => max(0, (int) floor(microtime(true) * 1000) - $requestStartedAtMs),
        ]);

        if (count($documents) === 1) {
            $redirect = redirect()
                ->route('documents.show', $documents[0])
                ->with('success', 'Documento enviado e adicionado a fila de processamento OCR.');

            if ($invalidFiles !== []) {
                $redirect->with('warning', $this->buildInvalidFilesWarning($invalidFiles));
            }

            return $redirect;
        }

        $redirect = redirect()
            ->route('history')
            ->with('success', count($documents).' documentos enviados e adicionados a fila de processamento OCR.');

        if ($invalidFiles !== []) {
            $redirect->with('warning', $this->buildInvalidFilesWarning($invalidFiles));
        }

        return $redirect;
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load([
            'uploader',
            'pages',
            'latestExtraction.fields',
            'processingJobs' => fn ($query) => $query->latest()->with('logs'),
            'versions' => fn ($query) => $query->latest(),
        ]);

        return view('documents.show', [
            'document' => $document,
            'previewUrl' => route('documents.preview', $document),
        ]);
    }

    public function edit(Document $document): RedirectResponse
    {
        return redirect()->route('documents.show', $document);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        return redirect()->route('documents.show', $document);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()
            ->route('history')
            ->with('success', 'Documento removido com sucesso.');
    }

    public function reprocess(Document $document, Request $request, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $this->authorize('reprocess', $document);

        $workflowService->dispatchProcessing($document, $request->user(), true);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Reprocessamento solicitado com sucesso.');
    }

    public function preview(Document $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        $disk = Storage::disk($document->storage_disk);
        $path = $disk->path($document->original_path);

        abort_unless($path && file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$document->original_filename.'"',
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk($document->storage_disk)->download($document->original_path, $document->original_filename);
    }

    /**
     * @return Collection<int, UploadedFile>
     */
    private function resolveUploadFiles(StoreDocumentRequest $request): Collection
    {
        $files = collect();

        $singleFile = $request->file('file');
        if ($singleFile instanceof UploadedFile) {
            $files->push($singleFile);
        }

        $multipleFiles = $request->file('files', []);
        foreach ($multipleFiles as $file) {
            if ($file instanceof UploadedFile) {
                $files->push($file);
            }
        }

        return $files->unique(function (UploadedFile $file): string {
            return implode('|', [
                $file->getClientOriginalName(),
                (string) $file->getSize(),
                md5_file($file->getRealPath() ?: $file->path()) ?: '',
            ]);
        })->values();
    }

    /**
     * @param  Collection<int, UploadedFile>  $files
     * @return array{
     *   valid: Collection<int, UploadedFile>,
     *   invalid: array<int, array{name: string, reasons: array<int, string>}>
     * }
     */
    private function partitionFilesForProcessing(Collection $files): array
    {
        $validFiles = collect();
        $invalidFiles = [];
        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
        $maxBytes = 50 * 1024 * 1024;

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: '');
            $sizeBytes = (int) ($file->getSize() ?? 0);
            $reasons = [];

            if (! in_array($extension, $allowedExtensions, true)) {
                $reasons[] = 'extensao invalida';
            }

            if ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
                $reasons[] = 'tamanho acima do limite';
            }

            if ($reasons === []) {
                $validFiles->push($file);

                continue;
            }

            $invalidFiles[] = [
                'name' => $file->getClientOriginalName(),
                'reasons' => $reasons,
            ];
        }

        return [
            'valid' => $validFiles->values(),
            'invalid' => $invalidFiles,
        ];
    }

    /**
     * @param  array<int, array{name: string, reasons: array<int, string>}>  $invalidFiles
     */
    private function buildInvalidFilesWarning(array $invalidFiles): string
    {
        $preview = collect($invalidFiles)
            ->take(3)
            ->map(fn (array $item): string => $item['name'])
            ->implode(', ');

        $suffix = count($invalidFiles) > 3 ? '...' : '';

        return count($invalidFiles).' arquivo(s) foram ignorados por formato/tamanho invalido: '.$preview.$suffix;
    }
}
