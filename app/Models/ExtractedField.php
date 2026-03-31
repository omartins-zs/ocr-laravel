<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractedField extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_extraction_id',
        'field_key',
        'label',
        'value',
        'normalized_value',
        'confidence',
        'source',
        'page_number',
        'is_validated',
        'validated_by',
        'validated_at',
        'validation_note',
        'metadata',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function extraction(): BelongsTo
    {
        return $this->belongsTo(DocumentExtraction::class, 'document_extraction_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
