<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $metadata = $this->input('metadata');

        if (is_string($metadata) && filled($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $this->merge([
            'metadata' => is_array($metadata) ? $metadata : [],
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'nullable',
                'file',
                'mimetypes:application/pdf,image/png,image/jpeg',
                'mimes:pdf,png,jpg,jpeg',
                'max:51200',
            ],
            'files' => [
                'nullable',
                'array',
                'min:1',
                'max:20',
            ],
            'files.*' => [
                'file',
                'max:51200',
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasFile('file') && ! $this->hasFile('files')) {
                $validator->errors()->add('files', 'Selecione pelo menos um arquivo para upload.');
            }

            if ($validator->errors()->isNotEmpty()) {
                Log::warning('upload.validation_failed', [
                    'route' => $this->path(),
                    'method' => $this->method(),
                    'has_file' => $this->hasFile('file'),
                    'has_files' => $this->hasFile('files'),
                    'files_count' => is_array($this->file('files')) ? count($this->file('files')) : 0,
                    'errors' => $validator->errors()->toArray(),
                ]);
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.max' => 'O arquivo deve ter no maximo 50MB.',
            'file.mimetypes' => 'Formato invalido. Envie PDF, PNG ou JPG.',
            'files.max' => 'Voce pode enviar no maximo 20 arquivos por lote.',
            'files.*.max' => 'Cada arquivo deve ter no maximo 50MB.',
        ];
    }
}
