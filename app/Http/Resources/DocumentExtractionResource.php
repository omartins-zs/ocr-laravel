<?php

namespace App\Http\Resources;

use App\Models\DocumentExtraction;
use App\Models\ExtractedField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentExtractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DocumentExtraction $extraction */
        $extraction = $this->resource;
        /** @var Collection<int, ExtractedField> $fields */
        $fields = $extraction->fields;

        return [
            'id' => $extraction->id,
            'document_id' => $extraction->document_id,
            'version' => $extraction->version,
            'source_engine' => $extraction->source_engine,
            'confidence' => $extraction->confidence,
            'language' => $extraction->language,
            'raw_text' => $extraction->raw_text,
            'normalized_text' => $extraction->normalized_text,
            'needs_review' => $extraction->needs_review,
            'reviewed_at' => optional($extraction->reviewed_at)->toIso8601String(),
            'review_notes' => $extraction->review_notes,
            'fields' => $this->whenLoaded('fields', static function () use ($fields): array {
                return $fields->map(
                    static fn (ExtractedField $field): array => [
                        'id' => $field->id,
                        'key' => $field->field_key,
                        'label' => $field->label,
                        'value' => $field->value,
                        'normalized_value' => $field->normalized_value,
                        'confidence' => $field->confidence,
                        'source' => $field->source,
                        'page_number' => $field->page_number,
                        'is_validated' => $field->is_validated,
                    ],
                )->values()->all();
            }),
        ];
    }
}
