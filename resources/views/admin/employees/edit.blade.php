@extends('layouts.admin')

@section('title', 'Modifier l\'employé')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Modifier : {{ $employee->name }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Mettez à jour les informations, départements et code PIN de l'employé</p>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour à la liste
        </a>
    </div>

    <form action="{{ route('admin.employees.update', $employee) }}" method="POST" class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="sm:col-span-2">
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom complet *</label>
                <input type="text" name="name" id="name" required value="{{ old('name', $employee->name) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Changer mot de passe</label>
                <input type="password" name="password" id="password"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                       placeholder="Laisser vide si inchangé">
            </div>

            <div>
                <label for="pin_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Code PIN à 4 chiffres *</label>
                <input type="text" name="pin_code" id="pin_code" required maxlength="4" pattern="\d{4}"
                       value="{{ old('pin_code', $employee->pin_code) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 font-mono font-bold text-center tracking-widest text-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </div>

            <div>
                <label for="is_active" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Statut du compte *</label>
                <select name="is_active" id="is_active" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    <option value="1" {{ $employee->is_active ? 'selected' : '' }}>Actif (peut pointer)</option>
                    <option value="0" {{ !$employee->is_active ? 'selected' : '' }}>Désactivé (bloqué)</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Départements assignés</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php $assigned = $employee->departments->pluck('id')->toArray(); @endphp
                    @foreach($departments as $dept)
                        <label class="flex items-center p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" 
                                   {{ in_array($dept->id, $assigned) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700 font-medium">{{ $dept->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-6 border-t border-slate-100">
            <form action="{{ route('admin.employees.regenerate-qr', $employee) }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Régénérer le QR Code rendra l\'ancien badge obsolète. Continuer ?')"
                        class="text-xs text-amber-700 hover:text-amber-900 font-medium">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Régénérer un nouveau QR Code
                </button>
            </form>

            <div class="flex space-x-3">
                <a href="{{ route('admin.employees.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">
                    Annuler
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">
                    Mettre à jour
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

