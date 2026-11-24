<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') - ChronoStaff</title>

    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        accent: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased">

    {{-- Bannière Impersonation / Support Super-Admin --}}
    @if(session('impersonator_id'))
        <div class="bg-amber-500 text-slate-950 font-bold text-xs px-4 py-2 flex items-center justify-between shadow-xs z-50">
            <span class="flex items-center"><i class="fa-solid fa-triangle-exclamation mr-2 text-sm"></i> Mode Support Super-Admin &mdash; Vous visualisez l'espace de : {{ Auth::user()->company->name ?? 'cette entreprise' }}</span>
            <form action="{{ route('superadmin.impersonate.leave') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1 bg-slate-950 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition flex items-center">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i> Quitter et revenir au Super-Admin
                </button>
            </form>
        </div>
    @endif

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center flex-shrink-0 mr-3">
                        <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="h-14 sm:h-16 w-auto object-contain max-h-16">
                    </a>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden xl:flex space-x-1 pl-2">
                        @if(Auth::user() && Auth::user()->role === 'superadmin')
                            <a href="{{ route('superadmin.companies.index') }}" 
                               class="px-3 py-2 rounded-lg text-sm font-bold whitespace-nowrap transition bg-amber-100 text-amber-900 border border-amber-300 mr-2">
                                <i class="fa-solid fa-crown mr-1.5 text-amber-600"></i> Super-Admin
                            </a>
                        @endif
                        <a href="{{ route('admin.dashboard') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-users-viewfinder mr-1.5 text-brand-600"></i> Présences
                        </a>
                        <a href="{{ route('admin.employees.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('admin.employees.*') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-id-card-clip mr-1.5 text-brand-600"></i> Employés
                        </a>
                        <a href="{{ route('admin.departments.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('admin.departments.*') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-sitemap mr-1.5 text-brand-600"></i> Départements
                        </a>
                        <a href="{{ route('admin.reports.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('admin.reports.*') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-chart-column mr-1.5 text-brand-600"></i> Rapports
                        </a>
                        <a href="{{ route('admin.adjustments.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('admin.adjustments.*') ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-pen-to-square mr-1.5 text-brand-600"></i> Ajustements
                        </a>
                    </nav>
                </div>

                <!-- Compact account menu keeps settings and logout reachable at every width. -->
                <div class="relative flex items-center space-x-3 pl-3 border-l border-slate-200 my-3" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-slate-500">Administrateur</span>
                    </div>
                    <button type="button"
                            class="w-10 h-10 inline-flex items-center justify-center text-slate-700 hover:bg-slate-100 transition"
                            aria-label="Ouvrir le menu du compte"
                            aria-controls="admin-account-menu"
                            :aria-expanded="menuOpen.toString()"
                            @click="menuOpen = !menuOpen">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div id="admin-account-menu"
                         x-cloak
                         x-show="menuOpen"
                         x-transition.origin.top.right
                         @click.away="menuOpen = false"
                         class="absolute right-0 top-full z-50 mt-2 w-72 bg-white border border-slate-200 shadow-xl">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <div class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-500">{{ Auth::user()->email }}</div>
                        </div>

                        <nav class="xl:hidden p-2 border-b border-slate-100" aria-label="Navigation principale">
                            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Présences</a>
                            <a href="{{ route('admin.employees.index') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Employés</a>
                            <a href="{{ route('admin.departments.index') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Départements</a>
                            <a href="{{ route('admin.reports.index') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Rapports</a>
                            <a href="{{ route('admin.adjustments.index') }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Ajustements</a>
                        </nav>

                        <div class="p-2">
                            <a href="{{ route('admin.company.edit') }}#securite" class="flex items-center px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-user-gear w-5 mr-2 text-slate-500"></i> Profil et paramètres
                            </a>
                            <a href="/app" target="_blank" rel="noopener" class="flex items-center px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-mobile-screen w-5 mr-2 text-slate-500"></i> Ouvrir l'application mobile
                            </a>
                        </div>

                        <form action="{{ route('admin.logout') }}" method="POST" class="p-2 border-t border-slate-100">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-3 py-2.5 text-sm text-rose-700 hover:bg-rose-50 transition">
                                <i class="fa-solid fa-arrow-right-from-bracket w-5 mr-2"></i> Se déconnecter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg mr-3"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-sm">
                <div class="flex items-center mb-1">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg mr-2"></i>
                    <span class="font-semibold">Veuillez corriger les erreurs suivantes :</span>
                </div>
                <ul class="list-disc list-inside pl-4 text-xs space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        <p>ChronoStaff &copy; {{ date('Y') }} &mdash; Solution de pointage & gestion du temps pour PME</p>
    </footer>

    @stack('scripts')
</body>
</html>
