<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthCheckTest extends TestCase
{
    public function test_api_health_endpoint_returns_standard_payload(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('status_code', 200)
            ->assertJsonPath('message', 'API healthy')
            ->assertJsonStructure([
                'status',
                'status_code',
                'message',
                'data' => ['service', 'timestamp'],
                'errors',
            ]);
    }
}
