<?php

namespace App\Http\Resources;

use App\Models\ProcessingLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessingLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ProcessingLog $log */
        $log = $this->resource;

        return [
            'id' => $log->id,
            'document_id' => $log->document_id,
            'processing_job_id' => $log->processing_job_id,
            'level' => $log->level,
            'stage' => $log->stage,
            'message' => $log->message,
            'context' => $log->context,
            'logged_at' => optional($log->logged_at)->toIso8601String(),
        ];
    }
}
