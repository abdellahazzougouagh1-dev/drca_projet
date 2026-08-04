<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.email', 'admin@example.com');
    }

    public function test_non_admin_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_guest_can_register_without_being_authenticated(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nouveau compte',
            'email' => 'guest@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
        $response->assertJsonPath('message', 'Utilisateur créé avec succès.');
        $response->assertJsonPath('user.name', 'Nouveau compte');
        $response->assertJsonPath('user.email', 'guest@example.com');

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'api-token',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Nouveau compte',
            'email' => 'guest@example.com',
            'is_admin' => false,
        ]);

        $this->assertNotSame('secret1234', User::where('email', 'guest@example.com')->first()?->password);
    }
}