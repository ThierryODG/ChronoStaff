<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    /**
     * Record employee acceptance of Terms & Privacy Policy.
     */
    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'terms_accepted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Conditions d\'utilisation et politique de confidentialité acceptées.',
            'terms_accepted_at' => $user->terms_accepted_at->toIso8601String(),
        ]);
    }

    /**
     * Check if terms are accepted.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'accepted' => !is_null($user->terms_accepted_at),
            'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
        ]);
    }
}

