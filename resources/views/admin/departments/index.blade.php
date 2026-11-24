@extends('layouts.admin')

@section('title', 'Gestion des Départements')

@section('content')
<div class="space-y-6" x-data="{ editDept: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Départements</h1>
            <p class="text-sm text-slate-500 mt-0.5">Organisez vos équipes et segmentez les rapports d'heures</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire d'ajout rapide -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-fit">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center">
                <i class="fa-solid fa-folder-plus text-blue-600 mr-2"></i> Nouveau département
            </h2>

            <form action="{{ route('admin.departments.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom du département *</label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                           placeholder="Ex: Logistique, Vente, Cuisine...">
                </div>

                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow-sm transition">
                    Créer le département
                </button>
            </form>
        </div>

        <!-- Liste des départements existants -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                        <th class="py-3 px-6">Nom</th>
                        <th class="py-3 px-4">Salariés assignés</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($departments as $dept)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6 font-semibold text-slate-900">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mr-3">
                                        <i class="fa-solid fa-building text-xs"></i>
                                    </div>
                                    {{ $dept->name }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-slate-600">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    {{ $dept->users_count }} employé(s)
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                {{-- Bouton Modifier --}}
                                <button type="button"
                                        @click="editDept = { id: {{ $dept->id }}, name: '{{ addslashes($dept->name) }}' }"
                                        class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition inline-flex items-center">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Modifier
                                </button>

                                {{-- Bouton Supprimer --}}
                                <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST" class="inline" onsubmit="return confirm('Confirmer la suppression de ce département ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition inline-flex items-center">
                                        <i class="fa-solid fa-trash-can mr-1"></i> Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-8 text-center text-slate-400">
                                Aucun département pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL MODIFIER DÉPARTEMENT --}}
    <div x-show="editDept !== null" x-cloak class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-slate-200 animate-slide-up" @click.away="editDept = null">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Modifier le département</h3>
                </div>
                <button type="button" @click="editDept = null" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <template x-if="editDept">
                <form :action="'{{ url('admin/departments') }}/' + editDept.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom du département *</label>
                        <input type="text" name="name" x-model="editDept.name" required
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="editDept = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow transition">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection


