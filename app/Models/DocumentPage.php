<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'page_number',
        'image_path',
        'width',
        'height',
        'rotation',
        'text_content',
        'confidence',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'confidence' => 'decimal:2',
        'rotation' => 'decimal:2',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
