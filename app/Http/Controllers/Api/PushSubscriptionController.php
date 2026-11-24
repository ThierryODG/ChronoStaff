<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function publicKey(): JsonResponse
    {
        $publicKey = config('webpush.public_key');

        if (! $publicKey || ! config('webpush.private_key')) {
            return response()->json([
                'message' => 'Les notifications Push ne sont pas configurées sur le serveur.',
            ], 503);
        }

        return response()->json(['public_key' => $publicKey]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2048|url',
            'keys.p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'keys.auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        if (parse_url($validated['endpoint'], PHP_URL_SCHEME) !== 'https') {
            throw ValidationException::withMessages([
                'endpoint' => ['Le point de terminaison Push doit utiliser HTTPS.'],
            ]);
        }

        $p256dh = $this->decodeBase64Url($validated['keys']['p256dh']);
        $auth = $this->decodeBase64Url($validated['keys']['auth']);
        if (strlen($p256dh) !== 65 || strlen($auth) !== 16) {
            throw ValidationException::withMessages([
                'keys' => ['Les clés de cet appareil sont invalides. Réessayez d’activer les rappels.'],
            ]);
        }

        if (! $request->user()->is_active) {
            return response()->json(['message' => 'Ce compte est désactivé.'], 403);
        }

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $validated['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => 'aes128gcm',
            ]
        );

        return response()->json([
            'message' => 'Cet appareil est inscrit pour recevoir les rappels.',
            'subscription_id' => $subscription->id,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2048|url',
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        return response()->json(['message' => 'Inscription Push supprimée.']);
    }

    private function decodeBase64Url(string $value): string
    {
        $padding = str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode(strtr($value.$padding, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
