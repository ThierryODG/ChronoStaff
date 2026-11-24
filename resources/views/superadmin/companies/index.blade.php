<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoStaff Platform — Espace Propriétaire & Super-Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased" x-data="{ showNewModal: false, editModalData: null }">

    <!-- Super-Admin Topbar -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-30 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('superadmin.companies.index') }}" class="flex items-center space-x-3">
                        <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="h-12 w-auto object-contain brightness-0 invert">
                    </a>
                    <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        <i class="fa-solid fa-crown mr-1.5 text-amber-400"></i> Espace Propriétaire
                    </span>
                </div>

                <!-- Right actions -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-800 border border-slate-700 transition">
                        Abonnements
                    </a>
                    <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center shadow-xs" title="Vider tous les caches Laravel (routes, vues, config)">
                            <i class="fa-solid fa-broom text-amber-400 mr-2"></i> Vider le Cache
                        </button>
                    </form>

                    <div class="flex items-center space-x-3 pl-3 border-l border-slate-800 text-white text-xs">
                        <div class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 font-bold flex items-center justify-center">
                            SA
                        </div>
                        <div class="hidden md:block">
                            <div class="font-bold">{{ Auth::user()->name ?? 'Propriétaire' }}</div>
                            <div class="text-[11px] text-slate-400">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 transition" title="Se déconnecter">
                            <i class="fa-solid fa-power-off text-base"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm rounded-2xl flex items-center space-x-2 shadow-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 flex-shrink-0 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-2xl shadow-xs">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Entreprises Clientes</div>
                <div class="text-3xl font-black text-slate-900">{{ $systemInfo['total_companies'] }}</div>
                <div class="text-[11px] text-emerald-600 font-semibold"><i class="fa-solid fa-building mr-1"></i> Espaces déployés</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Utilisateurs</div>
                <div class="text-3xl font-black text-slate-900">{{ $systemInfo['total_users'] }}</div>
                <div class="text-[11px] text-blue-600 font-semibold"><i class="fa-solid fa-users mr-1"></i> Admins, managers & agents</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Environnement</div>
                <div class="text-2xl font-black text-slate-900 uppercase font-mono">{{ $systemInfo['environment'] }}</div>
                <div class="text-[11px] text-slate-500 font-mono">PHP {{ $systemInfo['php_version'] }} · L{{ $systemInfo['laravel_version'] }}</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Maintenance Rapide</div>
                <div class="pt-1">
                    <form action="{{ route('superadmin.system.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold rounded-xl transition flex items-center justify-center">
                            <i class="fa-solid fa-rotate mr-1.5"></i> Purger les caches
                        </button>
                    </form>
                </div>
                <div class="text-[10px] text-slate-400 text-center">Nettoie routes, config & vues</div>
            </div>
        </div>

        {{-- Section Entreprises & CRUD --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-building text-blue-600 mr-2.5"></i> Gestion des Entreprises
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Créez et gérez les comptes entreprises clientes de ChronoStaff</p>
                </div>

                <button type="button" @click="showNewModal = true" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i> Nouvelle Entreprise
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                            <th class="py-3 px-6">Entreprise</th>
                            <th class="py-3 px-4">Administrateur Principal</th>
                            <th class="py-3 px-4 text-center">Salariés</th>
                            <th class="py-3 px-4 text-center">Pointages</th>
                            <th class="py-3 px-4">Création</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($companies as $company)
                            @php
                                $adminUser = $company->users->firstWhere('role', 'admin') ?? $company->users->first();
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 font-semibold text-slate-900">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($company->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $company->name }}</div>
                                            <div class="text-xs text-slate-400 font-normal">{{ $company->address ?? 'Aucune adresse renseignée' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    @if($adminUser)
                                        <div class="font-semibold text-slate-800 text-xs">{{ $adminUser->name }}</div>
                                        <div class="text-slate-500 text-xs font-mono">{{ $adminUser->email }}</div>
                                    @else
                                        <span class="text-xs text-rose-500 italic">Aucun admin</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                        {{ $company->users_count }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center text-xs text-slate-600 font-mono">
                                    {{ $company->time_logs_count }}
                                </td>
                                <td class="py-4 px-4 text-xs text-slate-500">
                                    {{ $company->created_at ? $company->created_at->format('d/m/Y') : '—' }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    {{-- Se connecter en tant que cet admin (Impersonate) --}}
                                    <form action="{{ route('superadmin.companies.impersonate', $company) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition" title="Accéder directement à l'espace de cet admin">
                                            <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> Accéder
                                        </button>
                                    </form>

                                    {{-- Modifier --}}
                                    <button type="button"
                                            @click="editModalData = {
                                                id: {{ $company->id }},
                                                name: '{{ addslashes($company->name) }}',
                                                address: '{{ addslashes($company->address ?? '') }}',
                                                admin_name: '{{ addslashes($adminUser->name ?? '') }}',
                                                admin_email: '{{ addslashes($adminUser->email ?? '') }}'
                                            }"
                                            class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Modifier
                                    </button>

                                    {{-- Supprimer --}}
                                    <form action="{{ route('superadmin.companies.destroy', $company) }}" method="POST" class="inline" onsubmit="return confirm('Attention : supprimer cette entreprise supprimera TOUS ses employés et pointages. Confirmer ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition">
                                            <i class="fa-solid fa-trash mr-1"></i> Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                    Aucune entreprise pour le moment. Cliquez sur « Nouvelle Entreprise ».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    {{-- MODAL : NOUVELLE ENTREPRISE --}}
    <div x-show="showNewModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full space-y-5 shadow-2xl border border-slate-200" @click.away="showNewModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-building-circle-check"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Créer une Entreprise</h3>
                        <p class="text-xs text-slate-500">Un compte administrateur lui sera automatiquement associé</p>
                    </div>
                </div>
                <button type="button" @click="showNewModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('superadmin.companies.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom de l'entreprise *</label>
                    <input type="text" name="company_name" required placeholder="Ex: Cabinet Médical SantéPlus, Usine MetalPro..."
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Adresse physique (optionnel)</label>
                    <input type="text" name="address" placeholder="Ex: 12 Rue Principale, Ouagadougou"
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block mb-3">Compte Administrateur de l'Entreprise</span>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom du Directeur / Gérant *</label>
                            <input type="text" name="admin_name" required placeholder="Ex: Moussa Kaboré"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email de connexion Admin *</label>
                            <input type="email" name="admin_email" required placeholder="directeur@entreprise.com"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mot de passe temporaire *</label>
                            <input type="password" name="admin_password" required minlength="6" placeholder="••••••••"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" @click="showNewModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow transition">
                        Créer l'entreprise & l'admin
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL : MODIFIER ENTREPRISE & ADMIN --}}
    <div x-show="editModalData !== null" x-cloak class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full space-y-5 shadow-2xl border border-slate-200" @click.away="editModalData = null">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Modifier l'Entreprise</h3>
                        <p class="text-xs text-slate-500">Mettez à jour les coordonnées ou réinitialisez le mot de passe</p>
                    </div>
                </div>
                <button type="button" @click="editModalData = null" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <template x-if="editModalData">
                <form :action="'{{ url('superadmin/companies') }}/' + editModalData.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Raison sociale *</label>
                        <input type="text" name="name" x-model="editModalData.name" required
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Adresse</label>
                        <input type="text" name="address" x-model="editModalData.address"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="pt-2 border-t border-slate-100 space-y-3">
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">Administrateur</span>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom du gérant *</label>
                            <input type="text" name="admin_name" x-model="editModalData.admin_name" required
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email de l'admin *</label>
                            <input type="email" name="admin_email" x-model="editModalData.admin_email" required
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" name="admin_password" minlength="6" placeholder="••••••••"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3">
                        <button type="button" @click="editModalData = null" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow transition">
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</body>
</html>
