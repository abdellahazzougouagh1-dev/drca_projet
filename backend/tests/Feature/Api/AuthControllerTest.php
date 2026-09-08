<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    public function test_unauthenticated_api_requests_return_json_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
