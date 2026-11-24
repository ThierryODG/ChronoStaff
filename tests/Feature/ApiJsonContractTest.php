<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiJsonContractTest extends TestCase
{
    public function test_mobile_health_route_returns_json_even_when_html_is_accepted(): void
    {
        $response = $this->get('/api/time', [
            'Accept' => 'text/html',
        ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['server_time', 'timezone']);
    }

    public function test_mobile_api_validation_errors_are_json_even_when_html_is_accepted(): void
    {
        $response = $this->post('/api/auth/login', [], [
            'Accept' => 'text/html',
        ]);

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['message', 'errors']);
    }
}
