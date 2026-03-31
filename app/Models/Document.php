<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'uploaded_by',
        'storage_disk',
        'original_path',
        'original_filename',
        'mime_type',
        'extension',
        'file_size',
        'total_pages',
        'checksum',
        'status',
        'processing_stage',
        'has_native_text',
        'overall_confidence',
        'processed_at',
        'approved_at',
        'approved_by',
        'rejected_reason',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'status' => DocumentStatus::class,
        'processing_stage' => ProcessingStage::class,
        'has_native_text' => 'boolean',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'overall_confidence' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            if (blank($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class);
    }

    public function extractions(): HasMany
    {
        return $this->hasMany(DocumentExtraction::class);
    }

    public function latestExtraction(): HasOne
    {
        return $this->hasOne(DocumentExtraction::class)->latestOfMany('version');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ExtractedField::class);
    }

    public function processingJobs(): HasMany
    {
        return $this->hasMany(ProcessingJob::class);
    }

    public function latestProcessingJob(): HasOne
    {
        return $this->hasOne(ProcessingJob::class)->latestOfMany();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('original_filename', 'like', "%{$term}%")
                ->orWhere('uuid', 'like', "%{$term}%")
                ->orWhere('checksum', 'like', "%{$term}%");
        });
    }

    public function sizeLabel(): Attribute
    {
        return Attribute::get(fn () => number_format($this->file_size / 1024, 2).' KB');
    }
}
