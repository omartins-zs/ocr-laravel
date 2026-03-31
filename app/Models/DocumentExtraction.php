<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentExtraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'source_engine',
        'raw_text',
        'normalized_text',
        'language',
        'confidence',
        'extraction_payload',
        'needs_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'needs_review' => 'boolean',
        'extraction_payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ExtractedField::class);
    }
}
