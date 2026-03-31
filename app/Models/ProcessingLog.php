<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'processing_job_id',
        'document_id',
        'level',
        'stage',
        'message',
        'context',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
    ];

    public function processingJob(): BelongsTo
    {
        return $this->belongsTo(ProcessingJob::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
