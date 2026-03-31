<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentExtractionResource;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\Documents\DocumentIngestionService;
use App\Services\Documents\DocumentWorkflowService;
use App\Support\PipelineLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class DocumentApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $statusValues = collect(DocumentStatus::cases())->map->value->all();
        $request->validate([
            'status' => ['nullable', Rule::in($statusValues)],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:10,100'],
        ]);

        $documents = Document::query()
            ->with(['uploader'])
            ->search($request->string('q')->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request, DocumentIngestionService $ingestionService): JsonResponse
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
            'source' => 'api',
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
                'source' => 'api',
                'controller' => self::class,
                'action' => 'store',
                'upload_batch_uuid' => $uploadBatchUuid,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'resolved_files_count' => $resolvedFiles->count(),
                'invalid_files_count' => count($invalidFiles),
                'invalid_files' => $invalidFiles,
            ]);

            return response()->json([
                'message' => 'Nenhum arquivo valido encontrado. Envie PDF, PNG, JPG ou JPEG com ate 50MB.',
                'invalid_files' => $invalidFiles,
            ], 422);
        }

        PipelineLogger::info('upload.batch_received', [
            'source' => 'api',
            'route' => $request->path(),
            'method' => $request->method(),
            'controller' => self::class,
            'action' => 'store',
            'upload_batch_uuid' => $uploadBatchUuid,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
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
                'source' => 'api',
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
                'source' => 'api',
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
                    'source' => 'api',
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

            $documents[] = $document->load(['uploader']);
        }

        PipelineLogger::info('upload.controller.completed', [
            'source' => 'api',
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
            $resource = (new DocumentResource($documents[0]))
                ->additional([
                    'message' => '1 documento enviado com sucesso.',
                    'upload_batch_uuid' => $uploadBatchUuid,
                    'invalid_files_count' => count($invalidFiles),
                    'invalid_files' => $invalidFiles,
                ]);

            return $resource
                ->response()
                ->setStatusCode(201);
        }

        return DocumentResource::collection(collect($documents))
            ->additional([
                'message' => count($documents).' documentos enviados com sucesso.',
                'upload_batch_uuid' => $uploadBatchUuid,
                'invalid_files_count' => count($invalidFiles),
                'invalid_files' => $invalidFiles,
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        $this->authorize('view', $document);

        return new DocumentResource($document->load(['uploader', 'latestExtraction']));
    }

    public function status(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $processingJob = $document->processingJobs()->latest()->first();

        return response()->json([
            'document_uuid' => $document->uuid,
            'status' => $document->status->value,
            'stage' => $document->processing_stage->value,
            'processed_at' => optional($document->processed_at)->toIso8601String(),
            'last_error' => $document->last_error,
            'processing_job' => $processingJob ? [
                'job_uuid' => $processingJob->job_uuid,
                'status' => $processingJob->status->value,
                'stage' => $processingJob->stage->value,
                'attempts' => $processingJob->attempts,
                'started_at' => optional($processingJob->started_at)->toIso8601String(),
                'finished_at' => optional($processingJob->finished_at)->toIso8601String(),
            ] : null,
        ]);
    }

    public function text(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $extraction = $document->latestExtraction()->first();

        return response()->json([
            'document_uuid' => $document->uuid,
            'version' => $extraction?->version,
            'text' => $extraction?->raw_text,
            'normalized_text' => $extraction?->normalized_text,
        ]);
    }

    public function fields(Document $document): DocumentExtractionResource|JsonResponse
    {
        $this->authorize('view', $document);

        $extraction = $document->latestExtraction()->with('fields')->first();

        if (! $extraction) {
            return response()->json([
                'message' => 'Documento ainda nao possui extracao.',
            ], 404);
        }

        return new DocumentExtractionResource($extraction);
    }

    public function reprocess(
        Request $request,
        Document $document,
        DocumentWorkflowService $workflowService,
    ): JsonResponse {
        $this->authorize('reprocess', $document);

        $workflowService->dispatchProcessing($document, $request->user(), true);

        return response()->json([
            'message' => 'Reprocessamento solicitado.',
        ], 202);
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
}
