<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            !$user
            || $user->role !== 'superadmin'
            || !$user->is_active
            || !$user->email
            || strtolower($user->email) !== strtolower(config('owner.email'))
        ) {
            abort(403, 'Accès réservé au Super-Administrateur (Propriétaire de la plateforme).');
        }

        return $next($request);
    }
}
