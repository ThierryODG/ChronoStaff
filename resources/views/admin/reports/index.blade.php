@extends('layouts.admin')

@section('title', 'Rapports d\'heures et Paie')

@section('content')
<div class="space-y-6">
    <!-- Header with Dropdown Export (CSV, PDF, Word) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rapports & Synthèse des Heures</h1>
            <p class="text-sm text-slate-500 mt-0.5">Calcul précis des présences, heures normales, heures supplémentaires et retards</p>
        </div>

        <!-- Menu Déroulant d'Exportation -->
        <div class="mt-4 sm:mt-0 relative" x-data="{ openExport: false }" @click.outside="openExport = false">
            <button @click="openExport = !openExport" type="button" 
                    class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                <i class="fa-solid fa-file-export mr-2 text-base"></i>
                <span>Exporter le rapport</span>
                <i class="fa-solid fa-chevron-down ml-2 text-xs transition transform" :class="{ 'rotate-180': openExport }"></i>
            </button>

            <!-- Menu Options (CSV, PDF, Word) -->
            <div x-show="openExport" x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-30">
                <a href="{{ route('admin.reports.export-csv', request()->all()) }}" 
                   class="flex items-center px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-emerald-700 transition">
                    <i class="fa-solid fa-file-csv text-emerald-600 text-base w-6"></i>
                    <div>
                        <div class="font-bold">Format CSV (Excel)</div>
                        <div class="text-[10px] text-slate-400 font-normal">Tableur compatible Excel</div>
                    </div>
                </a>
                <a href="{{ route('admin.reports.export-pdf', request()->all()) }}" target="_blank"
                   class="flex items-center px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-700 transition">
                    <i class="fa-solid fa-file-pdf text-rose-600 text-base w-6"></i>
                    <div>
                        <div class="font-bold">Format PDF / Impression</div>
                        <div class="text-[10px] text-slate-400 font-normal">Document A4 imprimable</div>
                    </div>
                </a>
                <a href="{{ route('admin.reports.export-word', request()->all()) }}" 
                   class="flex items-center px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-700 transition">
                    <i class="fa-solid fa-file-word text-blue-600 text-base w-6"></i>
                    <div>
                        <div class="font-bold">Format Word (.doc)</div>
                        <div class="text-[10px] text-slate-400 font-normal">Document Microsoft Word</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="{{ route('admin.reports.index') }}" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Date début</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Date fin</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Département</label>
            <select name="department_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                <option value="">Tous les départements</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ $departmentId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition">
                <i class="fa-solid fa-filter mr-1"></i> Filtrer les résultats
            </button>
        </div>
    </form>

    <!-- Company Rules Badge summary with Work Hours -->
    <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-4 text-xs text-blue-900 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-clock text-blue-600 text-base"></i>
            <span><strong>Horaires normaux :</strong> <span class="font-bold underline">{{ $company->work_start_time ?? '08:00' }} - {{ $company->work_end_time ?? '17:00' }}</span> | Seuls les shifts <strong>sur site</strong> sont comptabilisés | Déduction pause : <strong>-{{ $company->break_deduction_minutes }} min</strong> si shift &ge; {{ $company->break_threshold_hours }}h</span>
        </div>
        <a href="{{ route('admin.company.edit') }}#calcul" class="font-semibold text-blue-700 hover:underline">Modifier les horaires &rarr;</a>
    </div>

    <!-- Employee Reports Cards -->
    <div class="space-y-6">
        @forelse($reportData['employees'] as $emp)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ open: true }">
                <!-- Header with summary -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-slate-50/60 cursor-pointer" @click="open = !open">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                            {{ strtoupper(substr($emp['name'], 0, 2)) }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ $emp['name'] }}</h2>
                            <p class="text-xs text-slate-500">{{ $emp['department'] }} &bull; {{ $emp['total_shifts'] }} shift(s) complété(s)</p>
                        </div>
                    </div>

                    <div class="mt-3 sm:mt-0 flex items-center space-x-5">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Heures Normales</span>
                            <span class="text-sm font-semibold text-slate-700">{{ $emp['total_regular_hours'] }} h</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-600 block tracking-wider">Heures Supp.</span>
                            <span class="text-sm font-bold text-emerald-700">{{ $emp['total_overtime_formatted'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Total Payé</span>
                            <span class="text-lg font-black text-blue-600">{{ $emp['total_formatted'] }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-slate-400 transition transform" :class="{ 'rotate-180': open }"></i>
                    </div>
                </div>

                <!-- Shift Details Table -->
                <div x-show="open" class="border-t border-slate-200 overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-2.5 px-6">Date</th>
                                <th class="py-2.5 px-4">Arrivée</th>
                                <th class="py-2.5 px-4">Départ</th>
                                <th class="py-2.5 px-4">Lieu</th>
                                <th class="py-2.5 px-4 text-right">Durée Validée</th>
                                <th class="py-2.5 px-4 text-right">Heures Supp.</th>
                                <th class="py-2.5 px-4 text-right">Retard</th>
                                <th class="py-2.5 px-6">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($emp['shifts'] as $shift)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-6 font-medium text-slate-900">{{ $shift['date'] }}</td>
                                    <td class="py-3 px-4 text-slate-600 font-mono">{{ $shift['in_time'] }}</td>
                                    <td class="py-3 px-4 text-slate-600 font-mono">{{ $shift['out_time'] ?? 'En cours...' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $shift['is_on_site'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $shift['is_on_site'] ? 'Sur site' : 'À distance' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900">{{ $shift['formatted_duration'] }}</td>
                                    <td class="py-3 px-4 text-right font-semibold text-emerald-600">{{ $shift['overtime_formatted'] }}</td>
                                    <td class="py-3 px-4 text-right font-medium {{ $shift['lateness_minutes'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $shift['lateness_formatted'] }}</td>
                                    <td class="py-3 px-6 text-slate-400 italic">{{ $shift['note'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-4 text-center text-slate-400">Aucun pointage pour cette période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400">
                <i class="fa-solid fa-chart-simple text-3xl mb-2 text-slate-300"></i>
                <p>Aucune donnée trouvée pour les filtres sélectionnés.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
