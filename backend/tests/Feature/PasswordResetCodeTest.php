<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_resets_password_revokes_tokens_and_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('existing');
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;
        $this->assertNotSame($code, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
        $payload = ['email' => $user->email, 'code' => $code, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->postJson('/api/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
    }

    public function test_expired_code_and_wrong_codes_are_rejected_and_attempts_limited(): void
    {
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => Hash::make('123456'), 'created_at' => now()->subMinutes(11)]);
        $payload = ['email' => $user->email, 'code' => '123456', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()]);
        $payload['code'] = '654321';
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
        }
        $payload['code'] = '123456';
        $this->postJson('/api/reset-password', $payload)->assertStatus(429);
    }

    public function test_unknown_email_returns_same_message_and_resend_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'unknown@example.com'])->assertOk();
        $this->assertSame($known->json(), $unknown->json());
        Notification::assertCount(1);
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(429);
    }
}
