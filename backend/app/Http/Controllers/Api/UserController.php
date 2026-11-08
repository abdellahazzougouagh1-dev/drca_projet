<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (! $currentUser || ! $currentUser->is_admin) {
            abort(403, 'Accès interdit.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (! $currentUser || ! $currentUser->is_admin) {
            abort(403, 'Accès interdit.');
        }

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }
    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get());
    }

    // Basculer l'état Actif / Inactif
    public function toggleStatus(Request $request, $id)
    {
        $currentUser = $request->user();

        if (! $currentUser || ! $currentUser->is_admin) {
            abort(403, 'Accès interdit.');
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'user' => $user
        ]);
    }

    /**
     * Envoie un code de vérification à 6 chiffres par email (SMTP) à l'utilisateur connecté.
     */
    public function sendPasswordCode(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->email) {
            return response()->json([
                'message' => 'Utilisateur introuvable ou aucune adresse email associée.'
            ], 400);
        }

        $email = trim($user->email);
        $key = 'user-pwd-code:' . hash('sha256', strtolower($email));
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Patientez {$seconds} seconde(s) avant de demander un nouveau code."
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            $user->notify(new PasswordResetCode($code));
        } catch (\Throwable $e) {
            Log::error('Erreur lors de l’envoi de l’email SMTP de réinitialisation: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de l’envoi de l’email. Vérifiez la configuration SMTP dans le fichier .env.',
                'error_detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'message' => "Un code de sécurité à 6 chiffres a été envoyé à l’adresse {$email}. Il est valable 10 minutes.",
            'email' => $email,
        ]);
    }

    /**
     * Modifie le mot de passe de l'utilisateur connecté (soit via le code email reçu, soit via le mot de passe actuel).
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'regex:/^[0-9]{6}$/'],
        ]);

        $hasCode = !empty($data['code']);
        $hasCurrent = !empty($data['current_password']);

        if (!$hasCode && !$hasCurrent) {
            return response()->json([
                'message' => 'Veuillez renseigner soit le code reçu par email, soit votre mot de passe actuel.'
            ], 422);
        }

        // Cas 1 : validation via le code à 6 chiffres envoyé par email
        if ($hasCode) {
            $key = 'user-pwd-check:' . hash('sha256', strtolower($user->email));
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['message' => 'Trop de tentatives erronées. Réessayez dans 10 minutes.'], 429);
            }
            RateLimiter::hit($key, 600);

            $token = DB::table('password_reset_tokens')->where('email', $user->email)->first();
            if (! $token || ! $token->created_at
                || Carbon::parse($token->created_at)->addMinutes(6)->isPast()
                || ! Hash::check($data['code'], $token->token)) {
                return response()->json([
                    'message' => 'Le code de vérification est incorrect ou a expiré (validité 10 min).'
                ], 422);
            }

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        } else {
            // Cas 2 : validation via le mot de passe actuel
            if (! Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Le mot de passe actuel saisi est incorrect.'
                ], 422);
            }
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'remember_token' => Str::random(60),
        ])->save();

        return response()->json([
            'message' => 'Votre mot de passe a été modifié avec succès !',
        ]);
    }
}