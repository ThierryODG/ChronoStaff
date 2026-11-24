@extends('layouts.admin')

@section('title', 'Créer un employé')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ajouter un employé</h1>
            <p class="text-sm text-slate-500 mt-0.5">Le code PIN à 4 chiffres et le QR Code sont générés automatiquement</p>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour à la liste
        </a>
    </div>

    <form action="{{ route('admin.employees.store') }}" method="POST" class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Nom complet -->
            <div class="sm:col-span-2">
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom complet *</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                       placeholder="Ex: Sophie Lambert">
            </div>

            <!-- Email (optionnel) -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email professionnel (optionnel)</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                       placeholder="sophie@entreprise.com">
                <p class="text-xs text-slate-400 mt-1">Nécessaire si l'employé se connecte via email/mot de passe</p>
            </div>

            <!-- Mot de passe (optionnel) -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mot de passe initial (optionnel)</label>
                <input type="password" name="password" id="password"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                       placeholder="Laisser vide pour générer aléatoirement">
            </div>

            <!-- Code PIN à 4 chiffres -->
            <div>
                <label for="pin_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Code PIN à 4 chiffres *</label>
                <input type="text" name="pin_code" id="pin_code" required maxlength="4" pattern="\d{4}"
                       value="{{ old('pin_code', $suggestedCredentials['pin_code']) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 font-mono font-bold text-center tracking-widest text-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                <p class="text-xs text-slate-400 mt-1">Utilisé sur la borne ou en cas d'oubli de QR code</p>
            </div>

            <!-- Taux horaire par défaut -->
            <div>
                <label for="hourly_rate" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Taux horaire (€/h, optionnel)</label>
                <input type="number" step="0.01" min="0" name="hourly_rate" id="hourly_rate" value="{{ old('hourly_rate') }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                       placeholder="Ex: 15.50">
            </div>

            <!-- Départements -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Assigner à un ou plusieurs départements</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($departments as $dept)
                        <label class="flex items-center p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700 font-medium">{{ $dept->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-6 border-t border-slate-100">
            <a href="{{ route('admin.employees.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">
                Annuler
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">
                Enregistrer l'employé
            </button>
        </div>
    </form>
</div>
@endsection

