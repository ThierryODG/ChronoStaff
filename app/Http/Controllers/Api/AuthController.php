<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User / Employee login with email and password.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Ce compte a été désactivé par l\'administrateur.',
            ], 403);
        }

        // Vérifier si la connexion individuelle est autorisée pour les employés
        if ($user->role === 'employee' && $user->company && !$user->company->individual_login_enabled) {
            return response()->json([
                'message' => 'La connexion individuelle des employés est désactivée. Veuillez utiliser la borne de pointage.',
            ], 403);
        }

        // Generate Sanctum token
        $deviceName = $validated['device_name'] ?? 'Android-Device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'pin_code' => $user->pin_code, // sent to employee so app can display/confirm it
                'qr_token' => $user->qr_token,
                'needs_terms_acceptance' => is_null($user->terms_accepted_at),
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'address' => $user->company->address,
                ] : null,
            ],
        ]);
    }

    /**
     * Connect a shared kiosk device using its auto-generated device code and access key.
     */
    public function kioskConnect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => 'required|string',
            'access_key' => 'required|string',
        ]);

        $kiosk = \App\Models\Kiosk::where('device_code', $validated['device_code'])->first();

        if (!$kiosk || !\Illuminate\Support\Facades\Hash::check($validated['access_key'], $kiosk->access_key)) {
            return response()->json([
                'message' => 'Identifiants de la borne incorrects.',
            ], 401);
        }

        if (!$kiosk->is_active) {
            return response()->json([
                'message' => 'Cette borne de pointage a été désactivée.',
            ], 403);
        }

        $kiosk->update(['last_seen_at' => now()]);

        // Générer un token pour la borne lié à l'entreprise
        $companyAdmin = $kiosk->company?->users()->where('role', 'admin')->first() ?? User::where('role', 'admin')->first();
        $token = $companyAdmin ? $companyAdmin->createToken('Kiosk-' . $kiosk->device_code)->plainTextToken : null;

        return response()->json([
            'message' => 'Borne connectée avec succès.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'kiosk' => [
                'id' => $kiosk->id,
                'name' => $kiosk->name,
                'device_code' => $kiosk->device_code,
                'company' => $kiosk->company ? [
                    'id' => $kiosk->company->id,
                    'name' => $kiosk->company->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout and revoke current device token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion effectuée avec succès.',
        ]);
    }

    /**
     * Get current authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'departments']);
        $latestPunch = $user->latestTimeLog;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'pin_code' => $user->pin_code,
                'qr_token' => $user->qr_token,
                'is_active' => $user->is_active,
                'needs_terms_acceptance' => is_null($user->terms_accepted_at),
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
                'current_status' => $latestPunch?->type === 'in' ? 'checked_in' : 'checked_out',
                'latest_punch' => $latestPunch,
                'company' => $user->company,
                'departments' => $user->departments,
            ],
        ]);
    }
}

