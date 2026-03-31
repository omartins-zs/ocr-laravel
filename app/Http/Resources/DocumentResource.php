<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Document $document */
        $document = $this->resource;

        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'status' => $document->status->value,
            'processing_stage' => $document->processing_stage->value,
            'upload_batch_uuid' => data_get($document->metadata, 'upload_batch_uuid'),
            'overall_confidence' => $document->overall_confidence,
            'has_native_text' => $document->has_native_text,
            'total_pages' => $document->total_pages,
            'uploaded_by' => $document->uploader?->only(['id', 'name', 'email']),
            'processed_at' => optional($document->processed_at)->toIso8601String(),
            'created_at' => optional($document->created_at)->toIso8601String(),
            'updated_at' => optional($document->updated_at)->toIso8601String(),
            'links' => [
                'self' => route('api.documents.show', $document),
                'status' => route('api.documents.status', $document),
                'text' => route('api.documents.text', $document),
                'fields' => route('api.documents.fields', $document),
            ],
        ];
    }
}
