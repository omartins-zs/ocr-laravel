<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingStage;
use App\Models\Document;
use App\Models\User;
use App\Support\PipelineLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentIngestionService
{
    public function __construct(private readonly DocumentWorkflowService $workflowService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(UploadedFile $file, User $user, array $payload = []): Document
    {
        $disk = config('ocr.storage_disk', 'local');
        $directory = 'documents/'.now()->format('Y/m/d');
        $traceContext = is_array($payload['trace'] ?? null) ? $payload['trace'] : [];
        $uploadBatchUuid = Arr::get($traceContext, 'upload_batch_uuid');
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        if (is_string($uploadBatchUuid) && $uploadBatchUuid !== '') {
            $metadata = array_merge($metadata, [
                'upload_batch_uuid' => $uploadBatchUuid,
                'upload_files_total' => Arr::get($traceContext, 'files_total'),
                'upload_file_index' => Arr::get($traceContext, 'file_index'),
            ]);
        }

        PipelineLogger::info('ingestion.started', [
            'user_id' => $user->id,
            'disk' => $disk,
            'directory' => $directory,
            'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
            'trace' => $traceContext,
            'file' => [
                'original_name' => $file->getClientOriginalName(),
                'extension' => strtolower($file->getClientOriginalExtension() ?: ''),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ],
        ]);

        $storedPath = Storage::disk($disk)->putFile($directory, $file);

        if ($storedPath === false) {
            PipelineLogger::error('upload.file_store_failed', [
                'user_id' => $user->id,
                'disk' => $disk,
                'directory' => $directory,
                'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                'trace' => $traceContext,
                'original_name' => $file->getClientOriginalName(),
            ]);

            throw new RuntimeException('Falha ao armazenar documento no disco configurado.');
        }

        PipelineLogger::info('upload.file_stored', [
            'user_id' => $user->id,
            'disk' => $disk,
            'stored_path' => $storedPath,
            'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
            'trace' => $traceContext,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $checksum = hash_file('sha256', $file->getRealPath() ?: $file->path());

        PipelineLogger::info('ingestion.checksum_generated', [
            'checksum_sha256' => $checksum,
            'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return DB::transaction(function () use ($file, $user, $metadata, $disk, $storedPath, $checksum, $traceContext, $uploadBatchUuid): Document {
            $document = Document::query()->create([
                'uploaded_by' => $user->id,
                'storage_disk' => $disk,
                'original_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->getClientOriginalExtension() ?: ''),
                'file_size' => $file->getSize() ?: 0,
                'checksum' => $checksum,
                'status' => DocumentStatus::Uploaded,
                'processing_stage' => ProcessingStage::Validation,
                'metadata' => $metadata,
            ]);

            PipelineLogger::info(
                'document.created',
                PipelineLogger::contextFor($document, null, [
                    'status' => $document->status->value,
                    'processing_stage' => $document->processing_stage->value,
                    'uploaded_by' => $document->uploaded_by,
                    'checksum_sha256' => $document->checksum,
                    'upload_batch_uuid' => is_string($uploadBatchUuid) ? $uploadBatchUuid : null,
                    'metadata' => $metadata,
                ]),
            );

            $processingJob = $this->workflowService->dispatchProcessing($document, $user, false, $traceContext);
            $freshDocument = $document->fresh();

            PipelineLogger::info(
                'ingestion.completed',
                PipelineLogger::contextFor($freshDocument, $processingJob, [
                    'status' => $freshDocument?->status?->value,
                    'processing_stage' => $freshDocument?->processing_stage?->value,
                ]),
            );

            return $document->refresh();
        });
    }
}
