<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

    public function test_non_admin_cannot_toggle_user_status(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/users/' . $target->id . '/toggle-status')
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_active' => false]);

        Sanctum::actingAs($admin);

        $this->patchJson('/api/users/' . $target->id . '/toggle-status')
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }

    public function test_registration_cannot_grant_admin_privileges(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Utilisateur test',
            'email' => 'new-user@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
            'is_admin' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_authenticated_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password-123')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/change-password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-password-456',
        ])->assertStatus(422);

        $this->postJson('/api/user/change-password', [
            'current_password' => 'old-password-123',
            'new_password' => 'new-password-456',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_authenticated_user_can_change_password_with_email_code_valid_for_ten_minutes(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'HamzaUser@example.com']);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/send-password-code')->assertOk();
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(8)]);

        $this->postJson('/api/user/change-password', [
            'code' => $code,
            'new_password' => 'new-secure-pass-789',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secure-pass-789', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // Code is single-use
        $this->postJson('/api/user/change-password', [
            'code' => $code,
            'new_password' => 'another-pass-999',
        ])->assertStatus(422);
    }

    public function test_pgsql_connection_disables_server_prepared_statements_for_pooler(): void
    {
        $options = config('database.connections.pgsql.options');
        $attr = PHP_VERSION_ID >= 80500
            ? \Pdo\Pgsql::ATTR_DISABLE_PREPARES
            : \PDO::PGSQL_ATTR_DISABLE_PREPARES;

        $this->assertIsArray($options);
        $this->assertArrayHasKey($attr, $options);
        $this->assertTrue($options[$attr]);
    }
}