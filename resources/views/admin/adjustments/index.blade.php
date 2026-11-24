@extends('layouts.admin')

@section('title', 'Ajustements & Justifications')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ajustements & Justifications</h1>
            <p class="text-sm text-slate-500 mt-0.5">Traitez les justifications d'absence/retard des salariés et régularisez les pointages</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm rounded-2xl flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 flex-shrink-0 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- 1. Justifications transmises par les Salariés (Mobile) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Demandes de Justification des Salariés</h2>
                    <p class="text-xs text-slate-500">Motifs de retard ou d'absence envoyés depuis l'application mobile</p>
                </div>
            </div>
            <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-bold">
                {{ $justifications->where('status', 'pending')->count() }} en attente
            </span>
        </div>

        @if($justifications->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-4">Salarié</th>
                            <th class="py-2.5 px-4">Date concernée</th>
                            <th class="py-2.5 px-4">Type</th>
                            <th class="py-2.5 px-6">Motif de la justification</th>
                            <th class="py-2.5 px-4">Statut</th>
                            <th class="py-2.5 px-4 text-right">Décision direction</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($justifications as $j)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $j->user?->name ?? 'Employé' }}</td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ $j->date->format('d/m/Y') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $j->type === 'retard' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $j->type }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-slate-700 max-w-xs">{{ $j->reason }}</td>
                                <td class="py-3 px-4">
                                    @if($j->status === 'pending')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">En attente</span>
                                    @elseif($j->status === 'approved')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Approuvée</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Rejetée</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    @if($j->status === 'pending')
                                        <form action="{{ route('admin.adjustments.justifications.status', $j) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                                                <i class="fa-solid fa-check mr-1"></i> Valider
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.adjustments.justifications.status', $j) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition">
                                                <i class="fa-solid fa-xmark mr-1"></i> Refuser
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 text-[11px] italic">Traité</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-slate-400 text-xs bg-slate-50 rounded-xl border border-slate-100">
                Aucune demande de justification soumise par les salariés pour le moment.
            </div>
        @endif
    </div>

    <!-- 2. Formulaire d'Ajustement Manuel & Historique -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire d'ajout -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-fit">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center">
                <i class="fa-solid fa-pen-nib text-blue-600 mr-2"></i> Ajouter un pointage manuel
            </h2>

            <form action="{{ route('admin.adjustments.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="user_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Employé *</label>
                    <select name="user_id" id="user_id" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        <option value="">Sélectionner un employé...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Type de pointage *</label>
                    <select name="type" id="type" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        <option value="in">Arrivée (IN)</option>
                        <option value="out">Départ (OUT)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="punch_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Date *</label>
                        <input type="date" name="punch_date" id="punch_date" required value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="punch_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Heure *</label>
                        <input type="time" name="punch_time" id="punch_time" required value="{{ date('H:i') }}"
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="admin_note" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Motif obligatoire de la modification *
                    </label>
                    <textarea name="admin_note" id="admin_note" rows="3" required minlength="5"
                              placeholder="Ex: Oubli de badger ce matin, validé suite à un appel avec le responsable."
                              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-slate-900 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Obligatoire pour la conformité et la traçabilité</p>
                </div>

                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow-sm transition">
                    Valider le pointage manuel
                </button>
            </form>
        </div>

        <!-- Historique des ajustements -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-slate-600 mr-2"></i> Historique des modifications manuelles
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-4">Date & Heure</th>
                            <th class="py-2.5 px-4">Employé</th>
                            <th class="py-2.5 px-4">Type</th>
                            <th class="py-2.5 px-6">Motif / Justification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($manualLogs as $log)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                    {{ $log->punched_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    {{ $log->user->name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $log->type === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $log->type === 'in' ? 'Arrivée' : 'Départ' }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-slate-600 italic">
                                    {{ $log->admin_note }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400">
                                    Aucun ajustement manuel enregistré pour l'instant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $manualLogs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
