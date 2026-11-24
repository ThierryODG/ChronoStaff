@extends('layouts.admin')

@section('title', 'Tableau de bord - Qui est là ?')

@section('content')
<div x-data="{ 
    timer: 30, 
    init() { 
        setInterval(() => { 
            if (this.timer > 1) { 
                this.timer--; 
            } else { 
                window.location.reload(); 
            } 
        }, 1000); 
    } 
}">
    <!-- Header with title & refresh info -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center">
                <span class="inline-block w-3 h-3 bg-emerald-500 rounded-full animate-pulse mr-2.5"></span>
                Qui est là ? (Présence en direct)
            </h1>
            <p class="text-sm text-slate-500 mt-1">Surveillance instantanée des employés pointés sur site ou à distance</p>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                <i class="fa-solid fa-arrows-rotate mr-1.5 text-blue-600"></i>
                Actualisation dans <span x-text="timer" class="font-bold ml-1 mr-0.5">30</span>s
            </span>
            <button onclick="window.location.reload()" 
                    class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-medium hover:bg-slate-50 shadow-sm transition">
                <i class="fa-solid fa-rotate-right mr-1"></i> Actualiser
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mt-6">
        <!-- Present card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Actuellement Présents</p>
                <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ count($present) }}</p>
            </div>
        </div>

        <!-- Absent card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Non pointés (Absents)</p>
                <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ count($absent) }}</p>
            </div>
        </div>

        <!-- Total card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Effectif</p>
                <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ $employees->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Main Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
        
        <!-- Left: Employés Présents (2 colonnes sur grand écran) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-base font-bold text-slate-800 flex items-center">
                        <i class="fa-solid fa-circle text-emerald-500 text-xs mr-2"></i>
                        Employés Présents (Pointés "IN")
                    </h2>
                    <span class="text-xs text-slate-500 font-medium">{{ count($present) }} personne(s)</span>
                </div>

                @if(count($present) === 0)
                    <div class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-user-clock text-4xl mb-3 text-slate-300"></i>
                        <p class="text-sm font-medium">Aucun employé n'est actuellement pointé.</p>
                        <p class="text-xs text-slate-400 mt-1">Dès qu'un agent badge via l'application, il apparaîtra ici en temps réel.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                                    <th class="py-3 px-6">Employé</th>
                                    <th class="py-3 px-4">Département</th>
                                    <th class="py-3 px-4">Heure d'arrivée</th>
                                    <th class="py-3 px-4">Temps écoulé</th>
                                    <th class="py-3 px-6 text-right">Localisation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($present as $item)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-6 font-semibold text-slate-900 flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs mr-3">
                                                {{ strtoupper(substr($item['employee']->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div>{{ $item['employee']->name }}</div>
                                                <div class="text-xs font-normal text-slate-400">PIN : {{ $item['employee']->pin_code }}</div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                                {{ $item['department'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono font-medium text-slate-700">
                                            {{ $item['time'] }}
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600 font-medium">
                                            {{ $item['elapsed'] }}
                                        </td>
                                        <td class="py-3.5 px-6 text-right">
                                            @if($item['is_on_site'])
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    <i class="fa-solid fa-location-dot mr-1 text-emerald-600"></i> Sur site
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800" title="{{ $item['distance'] ? round($item['distance']).'m de l\'entreprise' : 'Distance inconnue' }}">
                                                    <i class="fa-solid fa-route mr-1 text-amber-600"></i> 
                                                    {{ $item['distance'] ? round($item['distance']).'m' : 'À distance' }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Employés Absents / Hors service -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-700 flex items-center">
                        <i class="fa-solid fa-circle text-slate-400 text-xs mr-2"></i>
                        Employés Non Pointés (Départ ou non arrivés)
                    </h2>
                    <span class="text-xs text-slate-500 font-medium">{{ count($absent) }} employé(s)</span>
                </div>

                @if(count($absent) === 0)
                    <div class="p-6 text-center text-slate-400 text-xs">
                        Tous les employés sont actuellement pointés.
                    </div>
                @else
                    <div class="overflow-x-auto max-h-64 overflow-y-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200 sticky top-0">
                                    <th class="py-2.5 px-6">Employé</th>
                                    <th class="py-2.5 px-4">Département</th>
                                    <th class="py-2.5 px-6 text-right">Dernière activité</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($absent as $item)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-2.5 px-6 font-medium text-slate-700">
                                            {{ $item['employee']->name }}
                                        </td>
                                        <td class="py-2.5 px-4 text-xs text-slate-500">
                                            {{ $item['department'] }}
                                        </td>
                                        <td class="py-2.5 px-6 text-right text-xs text-slate-400">
                                            {{ $item['last_seen'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Flux des Derniers Pointages -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center">
                    <i class="fa-solid fa-clock-rotate-left mr-2 text-blue-600"></i>
                    Dernières Activités
                </h2>

                @if($recentPunches->isEmpty())
                    <p class="text-xs text-slate-400 text-center py-6">Aucun pointage récent.</p>
                @else
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($recentPunches as $index => $punch)
                                <li>
                                    <div class="relative pb-6">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3 items-start">
                                            <div>
                                                @if($punch->type === 'in')
                                                    <span class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center ring-8 ring-white text-emerald-600 text-xs font-bold">
                                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                                    </span>
                                                @else
                                                    <span class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white text-slate-600 text-xs font-bold">
                                                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-xs font-semibold text-slate-900">
                                                        {{ $punch->user?->name ?? 'Inconnu' }}
                                                        <span class="font-normal text-slate-500">
                                                            {{ $punch->type === 'in' ? 'a pointé son arrivée' : 'a pointé son départ' }}
                                                        </span>
                                                    </p>
                                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                                        {{ $punch->is_on_site ? 'Sur site' : ($punch->distance_meters ? round($punch->distance_meters).'m de l\'entreprise' : 'Localisation enregistrée') }}
                                                        @if($punch->is_manual)
                                                            <span class="text-amber-600 font-semibold">(Ajustement manuel)</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="text-right text-xs whitespace-nowrap text-slate-400 font-mono">
                                                    {{ $punch->punched_at->format('H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

