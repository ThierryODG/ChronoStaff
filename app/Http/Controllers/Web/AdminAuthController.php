<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\OwnerLoginCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const OWNER_CODE_TTL_SECONDS = 600;
    private const OWNER_CODE_REQUEST_LIMIT = 3;
    private const OWNER_CODE_VERIFY_LIMIT = 5;

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($this->isConfiguredOwner($user)) {
                return redirect()->route('superadmin.companies.index');
            }

            if ($user->role === 'superadmin') {
                Auth::logout();
            } elseif ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('admin.auth.login');
    }

    public function showSuperAdminLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if ($this->isConfiguredOwner(Auth::user())) {
                return redirect()->route('superadmin.companies.index');
            }

            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('superadmin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Utilisez l’accès propriétaire disponible sur /admin.',
                ]);
            }

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte administrateur a été désactivé.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Identifiants incorrects.',
        ])->onlyInput('email');
    }

    public function requestOwnerLoginCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        $email = strtolower(trim($validated['email']));
        $requestKey = $this->ownerRateLimitKey('request', $email, $request);
        $request->session()->forget('owner_login_email');

        if (in_array(config('mail.default'), ['log', 'array', 'failover'], true)) {
            return back()->withErrors([
                'email' => 'L’envoi réel des emails n’est pas configuré. Configurez un serveur SMTP avant de demander un code.',
            ])->withInput();
        }

        if (RateLimiter::tooManyAttempts($requestKey, self::OWNER_CODE_REQUEST_LIMIT)) {
            return back()->withErrors([
                'email' => 'Trop de demandes. Réessayez dans quelques minutes.',
            ]);
        }

        RateLimiter::hit($requestKey, self::OWNER_CODE_TTL_SECONDS);
        $request->session()->put('owner_login_email', $email);

        $owner = $email === strtolower(config('owner.email'))
            ? User::query()
                ->where('email', $email)
                ->where('role', 'superadmin')
                ->where('is_active', true)
                ->first()
            : null;

        if (!$owner) {
            return back()->with('status', 'Si cette adresse correspond au compte propriétaire, un code temporaire a été envoyé. Vérifiez aussi vos courriers indésirables.');
        }

        $code = (string) random_int(100000, 999999);
        Mail::to($owner->email)->send(new OwnerLoginCodeMail($code));

        Cache::put(
            $this->ownerCodeCacheKey($owner->email),
            Hash::make($code),
            now()->addSeconds(self::OWNER_CODE_TTL_SECONDS)
        );
        RateLimiter::clear($this->ownerRateLimitKey('verify', $email, $request));
        $request->session()->put('owner_login_email', $owner->email);

        return back()->with('status', 'Si cette adresse correspond au compte propriétaire, un code temporaire a été envoyé. Vérifiez aussi vos courriers indésirables.');
    }

    public function verifyOwnerLoginCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|digits:6',
        ]);
        $email = $request->session()->get('owner_login_email');

        if (!$email) {
            return redirect()->route('superadmin.login')->withErrors([
                'code' => 'Demandez d’abord un nouveau code de connexion.',
            ]);
        }

        $verifyKey = $this->ownerRateLimitKey('verify', $email, $request);

        if (RateLimiter::tooManyAttempts($verifyKey, self::OWNER_CODE_VERIFY_LIMIT)) {
            Cache::forget($this->ownerCodeCacheKey($email));
            $request->session()->forget('owner_login_email');

            return redirect()->route('superadmin.login')->withErrors([
                'code' => 'Trop de codes incorrects. Demandez un nouveau code.',
            ]);
        }

        $owner = strtolower($email) === strtolower(config('owner.email'))
            ? User::query()
                ->where('email', $email)
                ->where('role', 'superadmin')
                ->where('is_active', true)
                ->first()
            : null;
        $storedCode = $owner ? Cache::get($this->ownerCodeCacheKey($email)) : null;

        if (!$owner || !$storedCode || !Hash::check($validated['code'], $storedCode)) {
            RateLimiter::hit($verifyKey, self::OWNER_CODE_TTL_SECONDS);

            if (RateLimiter::tooManyAttempts($verifyKey, self::OWNER_CODE_VERIFY_LIMIT)) {
                Cache::forget($this->ownerCodeCacheKey($email));
                $request->session()->forget('owner_login_email');
            }

            return back()->withErrors([
                'code' => 'Le code est invalide ou expiré. Vous pouvez en demander un nouveau.',
            ]);
        }

        Cache::forget($this->ownerCodeCacheKey($email));
        RateLimiter::clear($verifyKey);
        $request->session()->forget('owner_login_email');
        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->intended(route('superadmin.companies.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $isSuperAdmin = $request->user()?->role === 'superadmin';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($isSuperAdmin ? 'superadmin.login' : 'login');
    }

    private function isConfiguredOwner(?User $user): bool
    {
        return $user
            && $user->role === 'superadmin'
            && $user->is_active
            && $user->email
            && strtolower($user->email) === strtolower(config('owner.email'));
    }

    private function ownerCodeCacheKey(string $email): string
    {
        return 'owner-login-code:' . hash('sha256', strtolower($email));
    }

    private function ownerRateLimitKey(string $action, string $email, Request $request): string
    {
        return 'owner-login-' . $action . ':' . hash('sha256', strtolower($email) . '|' . $request->ip());
    }
}

