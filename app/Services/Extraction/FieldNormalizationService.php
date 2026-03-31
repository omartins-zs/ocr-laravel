<?php

namespace App\Services\Extraction;

class FieldNormalizationService
{
    public function normalize(string $fieldKey, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return match ($fieldKey) {
            'cpf', 'cnpj', 'rg', 'telefone', 'numero_documento' => preg_replace('/\D+/', '', $normalized),
            'email' => mb_strtolower($normalized),
            'valor' => $this->normalizeCurrency($normalized),
            default => preg_replace('/\s+/', ' ', $normalized),
        };
    }

    private function normalizeCurrency(string $value): string
    {
        $value = preg_replace('/[^\d,\.\-]/', '', $value) ?? '';

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }
}
