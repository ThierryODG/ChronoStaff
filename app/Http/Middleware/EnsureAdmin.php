<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            return response()->json([
                'message' => 'Accès refusé. Réservé aux administrateurs et managers de l’entreprise.',
            ], 403);
        }

        return $next($request);
    }
}

