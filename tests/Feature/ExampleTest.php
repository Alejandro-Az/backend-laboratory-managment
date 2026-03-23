<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_api_ping_uses_standard_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'ok',
                'data',
                'message',
            ])
            ->assertJson([
                'ok' => true,
                'message' => 'Success',
            ]);
    }

    public function test_protected_route_requires_token_and_returns_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure([
                'ok',
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJson([
                'ok' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }
}
