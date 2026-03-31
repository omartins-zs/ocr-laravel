<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDocumentFixtures;
use Tests\TestCase;

class OcrStatusEndpointTest extends TestCase
{
    use CreatesDocumentFixtures;
    use RefreshDatabase;

    public function test_guest_is_redirected_from_ocr_status_endpoint(): void
    {
        $response = $this->get(route('ocr.status'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_fetch_ocr_status(): void
    {
        config([
            'ocr.enabled' => false,
            'ocr.service_url' => 'http://127.0.0.1:8001',
        ]);

        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->getJson(route('ocr.status'));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('status_code', 200)
            ->assertJsonPath('data.state', 'disabled')
            ->assertJsonPath('data.label', 'desligado')
            ->assertJsonStructure([
                'status',
                'status_code',
                'message',
                'data' => [
                    'state',
                    'label',
                    'enabled',
                    'reachable',
                    'http_status',
                    'latency_ms',
                    'base_url',
                    'health_url',
                    'host',
                    'error',
                    'checked_at',
                ],
                'errors',
            ]);
    }
}
