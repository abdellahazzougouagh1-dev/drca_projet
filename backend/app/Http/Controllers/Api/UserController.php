<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private function isDirector(?User $user): bool
    {
        return $user !== null && strtolower(trim((string) $user->role)) === 'directeur';
    }

    private function canManageUsers(?User $user): bool
    {
        return $user !== null && ($user->is_admin || $this->isDirector($user));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (! $this->canManageUsers($currentUser)) {
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

        if (! $this->canManageUsers($currentUser)) {
            abort(403, 'Accès interdit.');
        }

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        if ($this->isDirector($user)) {
            return response()->json([
                'message' => 'Impossible de supprimer un compte Directeur.',
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->delete();
        });

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

        if (! $this->canManageUsers($currentUser)) {
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
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            RateLimiter::clear($key);
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
        ], [
            'new_password.required' => 'Veuillez saisir un nouveau mot de passe.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'code.regex' => 'Le code de vérification doit contenir exactement 6 chiffres.',
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
            $email = trim((string) $user->email);
            $key = 'user-pwd-check:' . hash('sha256', strtolower($email));
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['message' => 'Trop de tentatives erronées. Réessayez dans 10 minutes.'], 429);
            }
            RateLimiter::hit($key, 600);

            $updated = DB::transaction(function () use ($user, $email, $data) {
                $token = DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->lockForUpdate()
                    ->first();

                if (! $token && $email !== $user->email) {
                    $token = DB::table('password_reset_tokens')
                        ->where('email', $user->email)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $token || ! $token->created_at
                    || Carbon::parse($token->created_at)->addMinutes(10)->isPast()
                    || ! Hash::check($data['code'], $token->token)) {
                    return false;
                }

                DB::table('password_reset_tokens')->where('email', $token->email)->delete();

                $user->forceFill([
                    'password' => Hash::make($data['new_password']),
                    'remember_token' => Str::random(60),
                ])->save();

                return true;
            });

            if (! $updated) {
                return response()->json([
                    'message' => 'Le code de vérification est incorrect ou a expiré (validité 10 min).'
                ], 422);
            }

            RateLimiter::clear($key);
        } else {
            // Cas 2 : validation via le mot de passe actuel
            if (! Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Le mot de passe actuel saisi est incorrect.'
                ], 422);
            }

            $user->forceFill([
                'password' => Hash::make($data['new_password']),
                'remember_token' => Str::random(60),
            ])->save();
        }

        return response()->json([
            'message' => 'Votre mot de passe a été modifié avec succès !',
        ]);
    }

    /**
     * Met à jour les informations de profil (et éventuellement la photo) de l'utilisateur connecté.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'telephone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'remove_avatar' => ['nullable', Rule::in([true, false, 0, 1, '0', '1', 'true', 'false'])],
        ], [
            'name.required' => 'Veuillez saisir votre nom complet.',
            'name.min' => 'Le nom complet doit contenir au moins 2 caractères.',
            'email.required' => 'Veuillez saisir une adresse email valide.',
            'email.email' => 'Le format de l’adresse email est invalide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 30 caractères.',
            'avatar.image' => 'Le fichier sélectionné doit être une image valide.',
            'avatar.mimes' => 'Formats d’image acceptés : JPG, PNG, WEBP ou GIF.',
            'avatar.max' => 'L’image de profil ne doit pas dépasser 5 Mo.',
        ]);

        $normalizedName = trim($validated['name']);
        if (mb_strlen($normalizedName) < 2) {
            throw ValidationException::withMessages([
                'name' => ['Le nom complet doit contenir au moins 2 caractères.'],
            ]);
        }

        $normalizedEmail = trim($validated['email']);
        $duplicateEmail = User::whereRaw('LOWER(email) = ?', [strtolower($normalizedEmail)])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($duplicateEmail) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse email est déjà utilisée par un autre compte.'],
            ]);
        }

        $telephone = isset($validated['telephone']) ? trim((string) $validated['telephone']) : '';

        $attributes = [
            'name' => $normalizedName,
            'email' => $normalizedEmail,
            'telephone' => $telephone !== '' ? $telephone : null,
        ];

        if ($request->hasFile('avatar')) {
            $attributes['avatar'] = $this->encodeUploadedAvatar($request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            $attributes['avatar'] = null;
        }

        $user->forceFill($attributes)->save();

        return response()->json([
            'message' => 'Votre profil a été mis à jour avec succès !',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Téléverse ou remplace uniquement la photo de profil de l'utilisateur connecté.
     */
    public function updateAvatar(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ], [
            'avatar.required' => 'Veuillez sélectionner une image à téléverser.',
            'avatar.image' => 'Le fichier sélectionné doit être une image valide.',
            'avatar.mimes' => 'Formats d’image acceptés : JPG, PNG, WEBP ou GIF.',
            'avatar.max' => 'L’image de profil ne doit pas dépasser 5 Mo.',
        ]);

        $avatarDataUri = $this->encodeUploadedAvatar($request->file('avatar'));

        $user->forceFill([
            'avatar' => $avatarDataUri,
        ])->save();

        return response()->json([
            'message' => 'Photo de profil mise à jour avec succès !',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Supprime la photo de profil de l'utilisateur connecté.
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $user->forceFill([
            'avatar' => null,
        ])->save();

        return response()->json([
            'message' => 'Photo de profil supprimée avec succès.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Valide, redimensionne (si GD est disponible) et encode l'image en Data URI compacte pour stockage durable en base.
     */
    private function encodeUploadedAvatar(UploadedFile $file): string
    {
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false || $raw === '') {
            throw ValidationException::withMessages([
                'avatar' => ['Impossible de lire le fichier image envoyé.'],
            ]);
        }

        $info = @getimagesizefromstring($raw);
        $allowedMimes = [
            'image/jpeg' => 'jpeg',
            'image/jpg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $detectedMime = is_array($info) ? strtolower((string) ($info['mime'] ?? '')) : '';
        if (! isset($allowedMimes[$detectedMime])) {
            throw ValidationException::withMessages([
                'avatar' => ['Format d’image non pris en charge. Utilisez JPG, PNG, GIF ou WEBP.'],
            ]);
        }

        $canUseGd = function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor');
        $canDecodeType = $canUseGd && (
            $detectedMime === 'image/jpeg'
            || $detectedMime === 'image/jpg'
            || $detectedMime === 'image/png'
            || ($detectedMime === 'image/gif' && function_exists('imagecreatefromgif'))
            || ($detectedMime === 'image/webp' && (imagetypes() & IMG_WEBP))
        );

        if ($canDecodeType) {
            $src = @imagecreatefromstring($raw);
            if ($src !== false) {
                if (($detectedMime === 'image/jpeg' || $detectedMime === 'image/jpg') && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($file->getRealPath());
                    $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
                    $angle = match ($orientation) {
                        3 => 180,
                        6 => -90,
                        8 => 90,
                        default => 0,
                    };
                    if ($angle !== 0) {
                        $rotated = @imagerotate($src, $angle, 0);
                        if ($rotated !== false) {
                            $src = $rotated;
                        }
                    }
                }

                $origW = max(1, imagesx($src));
                $origH = max(1, imagesy($src));
                $maxDim = 320;

                if ($origW > $maxDim || $origH > $maxDim) {
                    $scale = min($maxDim / $origW, $maxDim / $origH);
                    $newW = max(1, (int) round($origW * $scale));
                    $newH = max(1, (int) round($origH * $scale));
                } else {
                    $newW = $origW;
                    $newH = $origH;
                }

                $dst = imagecreatetruecolor($newW, $newH);
                $isPng = ($detectedMime === 'image/png');
                if ($isPng) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                } else {
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
                }

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                ob_start();
                if ($isPng) {
                    imagepng($dst, null, 6);
                    $outMime = 'image/png';
                } else {
                    imagejpeg($dst, null, 85);
                    $outMime = 'image/jpeg';
                }
                $encoded = ob_get_clean();
                unset($src, $dst);

                if (is_string($encoded) && $encoded !== '') {
                    return 'data:' . $outMime . ';base64,' . base64_encode($encoded);
                }
            }
        }

        if (strlen($raw) > 1572864) {
            throw ValidationException::withMessages([
                'avatar' => ['L’image est trop volumineuse (max 1,5 Mo).'],
            ]);
        }

        $outMime = $detectedMime === 'image/jpg' ? 'image/jpeg' : $detectedMime;

        return 'data:' . $outMime . ';base64,' . base64_encode($raw);
    }
}