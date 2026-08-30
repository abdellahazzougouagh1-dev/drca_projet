<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRoutesSmokeTest extends TestCase
{
    public function test_public_auth_routes_are_reachable_and_validate_input(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_all_protected_api_routes_require_sanctum_authentication(): void
    {
        $checkedRoutes = 0;

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            if (! in_array('auth:sanctum', $route->gatherMiddleware(), true)) {
                continue;
            }

            $method = collect($route->methods())
                ->reject(fn (string $method) => $method === 'HEAD')
                ->first();

            if (! $method) {
                continue;
            }

            $uri = '/'.$this->uriWithPlaceholderValues($route->uri());
            $response = $this->json($method, $uri);

            $response
                ->assertStatus(401)
                ->assertJson(['message' => 'Unauthenticated.']);

            $checkedRoutes++;
        }

        $this->assertGreaterThanOrEqual(60, $checkedRoutes);
    }

    private function uriWithPlaceholderValues(string $uri): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function (array $matches): string {
            $parameter = trim($matches[1], '?');

            return match ($parameter) {
                'documentType' => 'fiche-suivi',
                'type' => 'estimation_administrative',
                default => '1',
            };
        }, $uri);
    }
}
