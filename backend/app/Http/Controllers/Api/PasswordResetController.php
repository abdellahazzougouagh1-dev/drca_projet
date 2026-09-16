<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = trim($data['email']);
        $key = 'reset-send:'.hash('sha256', strtolower($email));
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['message' => 'Patientez une minute avant de demander un nouveau code.'], 429);
        }
        RateLimiter::hit($key, 60);
        $user = User::where('email', $email)->first();
        if ($user) {
            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );
            $user->notify(new PasswordResetCode($code));
        }
        return response()->json(['message' => 'Si un compte correspond à cette adresse, un code vous sera envoyé. Il est valable 10 minutes.']);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        $key = 'reset-check:'.hash('sha256', strtolower(trim($data['email'])));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez dans 10 minutes.'], 429);
        }
        RateLimiter::hit($key, 600);
        DB::transaction(function () use ($data) {
            $user = User::where('email', trim($data['email']))->first();
            $token = DB::table('password_reset_tokens')->where('email', $user?->email ?? trim($data['email']))->lockForUpdate()->first();
            if (! $user || ! $token || ! $token->created_at
                || Carbon::parse($token->created_at)->addMinutes(10)->isPast()
                || ! Hash::check($data['code'], $token->token)) {
                throw ValidationException::withMessages(['code' => 'Code incorrect ou expiré.']);
            }
            $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        });
        return response()->json(['message' => 'Mot de passe modifié. Vous pouvez vous connecter.']);
    }
}
