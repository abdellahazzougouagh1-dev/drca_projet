<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Ancien nom', 'email' => 'old@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => 'Nouveau nom',
            'email' => 'new@example.com',
            'is_admin' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Utilisateur mis à jour avec succès.');
        $response->assertJsonPath('user.name', 'Nouveau nom');
        $response->assertJsonPath('user.email', 'new@example.com');
        $response->assertJsonPath('user.is_admin', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau nom',
            'email' => 'new@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_non_admin_cannot_update_a_user(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/users/' . $target->id, [
            'name' => 'Tentative',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/users/' . $admin->id);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Vous ne pouvez pas supprimer votre propre compte.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/users/' . $user->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}