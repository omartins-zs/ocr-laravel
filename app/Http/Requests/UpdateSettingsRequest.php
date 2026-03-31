<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.id' => ['required', 'exists:settings,id'],
            'settings.*.value' => ['nullable', 'string'],
        ];
    }
}
