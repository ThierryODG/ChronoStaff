@extends('layouts.admin')

@section('title', 'Gestion des Employés & Justifications')

@section('content')
<div class="space-y-6" x-data="{ activeTab: window.location.hash ? window.location.hash.replace('#','') : 'employees', activeQr: null }">

    {{-- En-tête page --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Personnel & Suivi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gérez les salariés, badges QR et validez les justifications d'absence</p>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
            <a href="{{ route('admin.employees.create') }}" 
               class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-user-plus mr-2"></i> Ajouter un salarié
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 flex-shrink-0 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-2xl">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Onglets de Navigation --}}
    @php
        $pendingJustifs = $justifications->where('status', 'pending')->count();
    @endphp
    <div class="flex overflow-x-auto space-x-2 bg-white border border-slate-200 rounded-2xl p-1.5 shadow-xs">
        <button type="button"
                @click="activeTab = 'employees'; window.location.hash = 'employees'"
                :class="activeTab === 'employees' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition flex-shrink-0">
            <i class="fa-solid fa-users mr-2"></i>
            Liste des Salariés ({{ $employees->total() }})
        </button>

        <button type="button"
                @click="activeTab = 'justifications'; window.location.hash = 'justifications'"
                :class="activeTab === 'justifications' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition flex-shrink-0">
            <i class="fa-solid fa-file-pen mr-2"></i>
            Justifications
            @if($pendingJustifs > 0)
                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-black"
                      :class="activeTab === 'justifications' ? 'bg-white text-amber-600' : 'bg-amber-500 text-white'">
                    {{ $pendingJustifs }} en attente
                </span>
            @endif
        </button>

        <button type="button"
                @click="activeTab = 'manual'; window.location.hash = 'manual'"
                :class="activeTab === 'manual' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition flex-shrink-0">
            <i class="fa-solid fa-clock-rotate-left mr-2"></i>
            Ajustement Manuel
        </button>
    </div>

    {{-- ============================================================
         ONGLET 1 : LISTE DES SALARIÉS
    ============================================================ --}}
    <div x-show="activeTab === 'employees'" x-cloak class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                            <th class="py-3 px-6">Employé</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Département(s)</th>
                            <th class="py-3 px-4 text-center">Code PIN</th>
                            <th class="py-3 px-4 text-center">Badge QR</th>
                            <th class="py-3 px-4 text-center">Statut</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 font-semibold text-slate-900 flex items-center">
                                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm mr-3">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="text-slate-900 font-bold">{{ $employee->name }}</div>
                                        <div class="text-[11px] text-slate-400">ID #{{ $employee->id }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-600 text-xs font-mono">
                                    {{ $employee->email ?? '—' }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($employee->departments as $dept)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ $dept->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">Général</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center font-mono font-bold text-slate-800 bg-slate-50/50">
                                    {{ $employee->pin_code }}
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <button type="button" 
                                            @click="activeQr = { name: '{{ addslashes($employee->name) }}', token: '{{ $employee->qr_token }}', pin: '{{ $employee->pin_code }}' }"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition"
                                            title="Voir le QR Code">
                                        <i class="fa-solid fa-qrcode text-blue-600"></i>
                                        <span class="font-mono text-[11px]">{{ substr($employee->qr_token, 0, 6) }}...</span>
                                    </button>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($employee->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Actif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            Inactif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right space-x-1.5">
                                    <a href="{{ route('admin.employees.badge', $employee) }}" 
                                       target="_blank"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition"
                                       title="Imprimer le badge">
                                        <i class="fa-solid fa-print mr-1 text-emerald-600"></i> Badge
                                    </a>

                                    <a href="{{ route('admin.employees.edit', $employee) }}" 
                                       class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        <i class="fa-solid fa-pen mr-1"></i> Modifier
                                    </a>

                                    <form action="{{ route('admin.employees.toggle-status', $employee) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ $employee->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} transition">
                                            {{ $employee->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    Aucun employé enregistré pour l'instant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         ONGLET 2 : JUSTIFICATIONS D'ABSENCES & RETARDS
    ============================================================ --}}
    <div x-show="activeTab === 'justifications'" x-cloak class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Demandes de Justification</h2>
                    <p class="text-xs text-slate-500">Transmises par les employés depuis leur application mobile</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    {{ $pendingJustifs }} en attente de décision
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                            <th class="py-3 px-6">Employé</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Motif</th>
                            <th class="py-3 px-6">Explications / Détails écrits</th>
                            <th class="py-3 px-4">Statut</th>
                            <th class="py-3 px-6 text-right">Décision Direction</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($justifications as $justif)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 font-semibold text-slate-900">
                                    {{ $justif->user->name ?? 'Employé supprimé' }}
                                </td>
                                <td class="py-4 px-4 font-mono text-xs text-slate-700">
                                    {{ \Carbon\Carbon::parse($justif->date)->format('d/m/Y') }}
                                </td>
                                <td class="py-4 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                        {{ $justif->type === 'retard' ? 'bg-amber-100 text-amber-800' : ($justif->type === 'absence' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $justif->type)) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 max-w-xs">
                                    <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                                        {{ $justif->reason }}
                                    </div>
                                    @if($justif->admin_comment)
                                        <div class="text-[11px] text-slate-400 mt-1">
                                            <strong>Note direction :</strong> {{ $justif->admin_comment }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    @if($justif->status === 'approved')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-check mr-1"></i> Acceptée
                                        </span>
                                    @elseif($justif->status === 'rejected')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                            <i class="fa-solid fa-xmark mr-1"></i> Rejetée
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 animate-pulse">
                                            <i class="fa-solid fa-hourglass mr-1"></i> En attente
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right space-x-1.5">
                                    @if($justif->status === 'pending')
                                        <form action="{{ route('admin.adjustments.justifications.status', $justif) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs" title="Accepter la justification">
                                                <i class="fa-solid fa-check mr-1"></i> Valider
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.adjustments.justifications.status', $justif) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold transition border border-rose-200" title="Refuser">
                                                <i class="fa-solid fa-xmark mr-1"></i> Rejeter
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Décision enregistrée</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                    Aucune demande de justification pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ONGLET 3 : AJUSTEMENT MANUEL RAPIDE
    ============================================================ --}}
    <div x-show="activeTab === 'manual'" x-cloak class="space-y-4">
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm max-w-xl">
            <h2 class="text-base font-bold text-slate-900 mb-2 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-blue-600 mr-2"></i> Ajouter un pointage manuel
            </h2>
            <p class="text-xs text-slate-500 mb-6">Enregistrez un badgeage pour un salarié ayant oublié de pointer son arrivée ou son départ.</p>

            <form action="{{ route('admin.adjustments.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="user_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Employé concerné *</label>
                    <select name="user_id" id="user_id" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} (PIN: {{ $emp->pin_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Type de pointage *</label>
                        <select name="type" id="type" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <option value="in">Arrivée (IN)</option>
                            <option value="out">Départ (OUT)</option>
                        </select>
                    </div>
                    <div>
                        <label for="punched_at" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Date et Heure *</label>
                        <input type="datetime-local" name="punched_at" id="punched_at" required value="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Motif de l'ajustement</label>
                    <input type="text" name="reason" id="reason" placeholder="Ex: Oubli de badgeage le matin"
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow-md transition">
                    Valider le pointage manuel
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Aperçu Rapide QR Code -->
    <div x-show="activeQr !== null" 
         x-cloak
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="activeQr = null">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full text-center shadow-2xl border border-slate-200 space-y-4">
            <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900" x-text="activeQr ? activeQr.name : ''"></h3>
                <button @click="activeQr = null" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 inline-block">
                <template x-if="activeQr">
                    <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(activeQr.token)" 
                         alt="QR Code" 
                         class="w-52 h-52 object-contain mx-auto">
                </template>
            </div>

            <div class="text-xs space-y-1">
                <p class="text-slate-500 font-mono text-[11px]" x-text="'Jeton: ' + (activeQr ? activeQr.token : '')"></p>
                <p class="font-semibold text-slate-800" x-text="'Code PIN : ' + (activeQr ? activeQr.pin : '')"></p>
            </div>

            <button @click="activeQr = null" class="w-full py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold">
                Fermer
            </button>
        </div>
    </div>

</div>
@endsection
