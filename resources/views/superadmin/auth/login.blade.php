<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès propriétaire - ChronoStaff</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 bg-slate-100">
    <main class="max-w-md w-full bg-white p-8 sm:p-10 shadow-xl border border-slate-200">
        <div class="text-center">
            <img class="mx-auto h-16 w-auto object-contain" src="{{ asset('images/logo.png') }}" alt="ChronoStaff">
            <h1 class="mt-5 text-2xl font-bold text-slate-900">Espace propriétaire</h1>
            <p class="mt-2 text-sm text-slate-500">Connectez-vous avec un code temporaire envoyé par email.</p>
        </div>

        @if(session('status'))
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 p-4 bg-rose-50 border border-rose-200 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="mt-8 space-y-5" action="{{ route('superadmin.request-code') }}" method="POST">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Adresse email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', config('owner.email')) }}"
                       class="w-full px-3.5 py-3 border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                       placeholder="votre adresse email">
            </div>
            <button type="submit" class="w-full py-3 bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 transition">
                Recevoir un code de connexion
            </button>
        </form>

        @if(session()->has('owner_login_email'))
            <form class="mt-6 space-y-5" action="{{ route('superadmin.verify-code') }}" method="POST">
                @csrf
                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Code reçu par email</label>
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           autocomplete="one-time-code" required
                           class="w-full px-3.5 py-3 border border-slate-300 text-sm tracking-[0.4em] text-center focus:outline-none focus:ring-2 focus:ring-blue-600"
                           placeholder="000000">
                </div>
                <button type="submit" class="w-full py-3 bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 transition">
                    Vérifier et se connecter
                </button>
            </form>
        @endif
    </main>
</body>
</html>
