<?php

namespace Tests\Unit;

use App\Services\Extraction\FieldNormalizationService;
use Tests\TestCase;

class FieldNormalizationServiceTest extends TestCase
{
    public function test_it_normalizes_identifier_fields(): void
    {
        $service = new FieldNormalizationService;

        $normalized = $service->normalize('cpf', '123.456.789-10');

        $this->assertSame('12345678910', $normalized);
    }

    public function test_it_normalizes_email_to_lowercase(): void
    {
        $service = new FieldNormalizationService;

        $normalized = $service->normalize('email', 'USUARIO@EXEMPLO.COM');

        $this->assertSame('usuario@exemplo.com', $normalized);
    }

    public function test_it_normalizes_currency_values(): void
    {
        $service = new FieldNormalizationService;

        $normalized = $service->normalize('valor', 'R$ 1.234,56');

        $this->assertSame('1234.56', $normalized);
    }

    public function test_it_compacts_whitespace_for_default_fields(): void
    {
        $service = new FieldNormalizationService;

        $normalized = $service->normalize('nome', '  Maria   da   Silva  ');

        $this->assertSame('Maria da Silva', $normalized);
    }
}
