<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $user = User::factory()->create(['role' => 'gestionnaire']);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/users/' . $user->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_director_cannot_be_deleted_even_by_admin_or_another_director(): void
    {
        $actingDirector = User::factory()->create(['is_admin' => true, 'role' => 'directeur']);
        $targetDirector = User::factory()->create(['is_admin' => false, 'role' => 'directeur']);

        Sanctum::actingAs($actingDirector);

        $response = $this->deleteJson('/api/users/' . $targetDirector->id);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Impossible de supprimer un compte Directeur.');
        $this->assertDatabaseHas('users', ['id' => $targetDirector->id]);
    }

    public function test_director_without_is_admin_flag_can_delete_gestionnaire(): void
    {
        $director = User::factory()->create(['is_admin' => false, 'role' => 'directeur']);
        $gestionnaire = User::factory()->create(['is_admin' => false, 'role' => 'gestionnaire']);

        Sanctum::actingAs($director);

        $this->deleteJson('/api/users/' . $gestionnaire->id)
            ->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $gestionnaire->id]);
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

    public function test_authenticated_user_can_update_their_own_profile_without_escalating_privileges(): void
    {
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'agent@example.com',
            'role' => 'gestionnaire',
            'is_admin' => false,
            'is_active' => true,
        ]);
        $other = User::factory()->create(['email' => 'Autre@Example.com']);

        Sanctum::actingAs($user);

        // Case-insensitive duplicate email must be rejected
        $this->postJson('/api/user/profile', [
            'name' => 'Nouveau Nom',
            'email' => 'autre@example.com',
        ])->assertStatus(422);

        // Valid update must persist profile fields while ignoring role/is_admin/is_active
        $response = $this->postJson('/api/user/profile', [
            'name' => 'Hamza Baadi',
            'email' => 'hamza.new@example.com',
            'telephone' => '0612345678',
            'role' => 'directeur',
            'is_admin' => true,
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Hamza Baadi')
            ->assertJsonPath('user.email', 'hamza.new@example.com')
            ->assertJsonPath('user.telephone', '0612345678');

        $fresh = $user->fresh();
        $this->assertSame('Hamza Baadi', $fresh->name);
        $this->assertSame('hamza.new@example.com', $fresh->email);
        $this->assertSame('0612345678', $fresh->telephone);
        $this->assertSame('gestionnaire', $fresh->role);
        $this->assertFalse((bool) $fresh->is_admin);
        $this->assertTrue((bool) $fresh->is_active);
    }

    public function test_authenticated_user_can_upload_and_delete_profile_avatar(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Non-image upload rejected
        $fakeSvg = UploadedFile::fake()->createWithContent('avatar.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->post('/api/user/avatar', ['avatar' => $fakeSvg], ['Accept' => 'application/json'])
            ->assertStatus(422);

        // Valid PNG image accepted and stored as data URI
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $validPng = UploadedFile::fake()->createWithContent('avatar.png', $pngBytes);

        $uploadRes = $this->post('/api/user/avatar', ['avatar' => $validPng], ['Accept' => 'application/json']);
        $uploadRes->assertOk();
        $this->assertStringStartsWith('data:image/png;base64,', (string) $user->fresh()->avatar);

        // Delete avatar
        $this->deleteJson('/api/user/avatar')
            ->assertOk()
            ->assertJsonPath('user.avatar', null);

        $this->assertNull($user->fresh()->avatar);
    }
}