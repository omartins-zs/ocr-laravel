<?php

namespace Tests\Unit;

use App\Services\Ocr\OcrServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class OcrServiceClientTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_it_returns_success_payload_from_ocr_service(): void
    {
        config(['ocr.service_url' => 'http://ocr.test']);

        $user = $this->createUser();
        $document = $this->createDocument($user);
        $processingJob = $this->createProcessingJob($document, $user);

        Http::fake([
            'http://ocr.test/api/v1/process' => Http::response([
                'status' => 'success',
                'document' => [
                    'engine' => 'tesseract',
                    'confidence' => 98.1,
                    'total_pages' => 1,
                ],
                'text' => 'Texto OCR',
                'normalized_text' => 'Texto OCR',
                'pages' => [],
                'fields' => [],
                'warnings' => [],
                'metadata' => [],
            ], 200),
        ]);

        $client = app(OcrServiceClient::class);
        $result = $client->process($document, $processingJob);

        $this->assertSame('success', $result['status']);
        $this->assertSame('tesseract', $result['document']['engine']);

        Http::assertSent(function ($request) use ($document, $processingJob): bool {
            return $request->url() === 'http://ocr.test/api/v1/process'
                && $request['document_id'] === $document->id
                && $request['processing_job_uuid'] === $processingJob->job_uuid;
        });
    }

    public function test_it_throws_exception_when_http_response_is_not_successful(): void
    {
        config(['ocr.service_url' => 'http://ocr.test']);

        $user = $this->createUser();
        $document = $this->createDocument($user);
        $processingJob = $this->createProcessingJob($document, $user);

        Http::fake([
            'http://ocr.test/api/v1/process' => Http::response([
                'message' => 'OCR indisponivel',
            ], 500),
        ]);

        $client = app(OcrServiceClient::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OCR service returned HTTP 500');

        $client->process($document, $processingJob);
    }
}
