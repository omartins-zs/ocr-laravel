<?php

namespace App\Models;

use App\Enums\ProcessingJobStatus;
use App\Enums\ProcessingStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProcessingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'job_uuid',
        'queue_name',
        'status',
        'stage',
        'attempts',
        'max_attempts',
        'started_at',
        'finished_at',
        'duration_ms',
        'ocr_engine',
        'error_code',
        'error_message',
        'payload',
        'result_summary',
        'created_by',
    ];

    protected $casts = [
        'status' => ProcessingJobStatus::class,
        'stage' => ProcessingStage::class,
        'payload' => 'array',
        'result_summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            if (blank($job->job_uuid)) {
                $job->job_uuid = (string) Str::uuid();
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProcessingLog::class);
    }
}
