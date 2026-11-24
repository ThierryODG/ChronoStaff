@extends('layouts.admin')

@section('title', 'Paramètres de l\'entreprise')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- En-tête page --}}
    <div class="flex items-center justify-between pb-5 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Paramètres</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gérez toutes les configurations de votre espace ChronoStaff</p>
        </div>
    </div>

    {{-- Flash success --}}
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

    {{-- ================================================================
         ONGLETS DE NAVIGATION DES PARAMÈTRES
    ================================================================ --}}
    <div x-data="{ activeTab: window.location.hash ? window.location.hash.replace('#','') : 'localisation' }" class="space-y-6">

        {{-- Barre d'onglets --}}
        <div class="flex overflow-x-auto space-x-1 bg-white border border-slate-200 rounded-2xl p-1.5 shadow-sm">
            @php
                $tabs = [
                    ['id' => 'localisation', 'icon' => 'fa-location-dot', 'label' => 'Localisation', 'color' => 'blue'],
                    ['id' => 'calcul', 'icon' => 'fa-calculator', 'label' => 'Calcul & Pauses', 'color' => 'emerald'],
                    ['id' => 'acces', 'icon' => 'fa-sliders', 'label' => 'Accès & Bornes', 'color' => 'violet'],
                    ['id' => 'rappels', 'icon' => 'fa-bell', 'label' => 'Rappels', 'color' => 'amber'],
                    ['id' => 'managers', 'icon' => 'fa-user-shield', 'label' => 'Managers', 'color' => 'rose'],
                    ['id' => 'securite', 'icon' => 'fa-key', 'label' => 'Mon Compte & Mot de passe', 'color' => 'indigo'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button
                    type="button"
                    @click="activeTab = '{{ $tab['id'] }}'; window.location.hash = '{{ $tab['id'] }}'"
                    :class="activeTab === '{{ $tab['id'] }}' ? 'bg-{{ $tab['color'] }}-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'"
                    class="flex items-center whitespace-nowrap px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex-shrink-0">
                    <i class="fa-solid {{ $tab['icon'] }} mr-2"></i>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- ============================================================
             ONGLET 1 : LOCALISATION
        ============================================================ --}}
        <div x-show="activeTab === 'localisation'" x-cloak>
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Localisation de l'Entreprise</h2>
                        <p class="text-xs text-slate-500">Adresse et coordonnées GPS pour valider les pointages sur site</p>
                    </div>
                </div>

                <form action="{{ route('admin.company.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    {{-- Champs cachés pour préserver les autres onglets --}}
                    <input type="hidden" name="time_rounding" value="{{ $company->time_rounding ?? 'minute' }}">
                    <input type="hidden" name="break_threshold_hours" value="{{ $company->break_threshold_hours ?? 6 }}">
                    <input type="hidden" name="break_deduction_minutes" value="{{ $company->break_deduction_minutes ?? 30 }}">
                    <input type="hidden" name="cooldown_enabled" value="{{ $company->cooldown_enabled ? '1' : '0' }}">
                    <input type="hidden" name="min_punch_interval_minutes" value="{{ $company->min_punch_interval_minutes ?? 60 }}">
                    <input type="hidden" name="work_start_time" value="{{ $company->work_start_time ?? '08:00' }}">
                    <input type="hidden" name="work_end_time" value="{{ $company->work_end_time ?? '17:00' }}">
                    <input type="hidden" name="overtime_enabled" value="{{ $company->overtime_enabled ? '1' : '0' }}">
                    <input type="hidden" name="require_onsite_for_reports" value="{{ $company->require_onsite_for_reports ? '1' : '0' }}">
                    <input type="hidden" name="timezone" value="{{ $company->timezone ?? 'Europe/Paris' }}">
                    <input type="hidden" name="kiosk_mode_enabled" value="{{ $company->kiosk_mode_enabled ? '1' : '0' }}">
                    <input type="hidden" name="individual_login_enabled" value="{{ $company->individual_login_enabled ? '1' : '0' }}">
                    <input type="hidden" name="reminder_enabled" value="{{ $company->reminder_enabled ? '1' : '0' }}">
                    <input type="hidden" name="reminder_time" value="{{ $company->reminder_time ?? '07:00' }}">
                    <input type="hidden" name="reminder_message" value="{{ $company->reminder_message ?? 'N\'oubliez pas de badger votre arrivée !' }}">

                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Raison sociale *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $company->name) }}"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>

                    <div>
                        <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Adresse complète</label>
                        <div class="flex space-x-2">
                            <input type="text" name="address" id="address" value="{{ old('address', $company->address) }}"
                                   class="flex-1 px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                                   placeholder="Ex: 10 Rue de la Paix, Paris, France">
                            <button type="button" onclick="searchAddress()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i> Localiser
                            </button>
                        </div>
                    </div>

                    <div id="company-map" class="w-full h-64 rounded-2xl border border-slate-200 overflow-hidden shadow-inner"></div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="latitude" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Latitude</label>
                            <input type="number" step="0.00000001" name="latitude" id="latitude" value="{{ old('latitude', $company->latitude ?? 48.8698) }}"
                                   class="w-full px-3.5 py-2 border border-slate-300 rounded-xl font-mono text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Longitude</label>
                            <input type="number" step="0.00000001" name="longitude" id="longitude" value="{{ old('longitude', $company->longitude ?? 2.3314) }}"
                                   class="w-full px-3.5 py-2 border border-slate-300 rounded-xl font-mono text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow transition">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================
             ONGLET 2 : CALCUL & PAUSES + ANTI-DOUBLE POINTAGE (COOLDOWN)
        ============================================================ --}}
        <div x-show="activeTab === 'calcul'" x-cloak>
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Règles de Calcul & Anti-Double Pointage</h2>
                        <p class="text-xs text-slate-500">Arrondi horaire, déduction de pause et délai minimum entre Arrivée et Départ</p>
                    </div>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-xs text-emerald-900 space-y-1.5 leading-relaxed">
                    <p class="font-bold flex items-center text-sm"><i class="fa-solid fa-mug-hot mr-2 text-emerald-600"></i> Comment fonctionne la pause automatique ?</p>
                    <p>Vos salariés badgent <strong>2 fois par jour</strong> seulement (arrivée + départ). Le système déduit automatiquement la pause déjeuner si le shift dépasse le seuil configuré.</p>
                </div>

                <form action="{{ route('admin.company.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    {{-- Champs cachés pour préserver les autres onglets --}}
                    <input type="hidden" name="name" value="{{ $company->name }}">
                    <input type="hidden" name="address" value="{{ $company->address }}">
                    <input type="hidden" name="latitude" value="{{ $company->latitude }}">
                    <input type="hidden" name="longitude" value="{{ $company->longitude }}">
                    <input type="hidden" name="kiosk_mode_enabled" value="{{ $company->kiosk_mode_enabled ? '1' : '0' }}">
                    <input type="hidden" name="individual_login_enabled" value="{{ $company->individual_login_enabled ? '1' : '0' }}">
                    <input type="hidden" name="reminder_enabled" value="{{ $company->reminder_enabled ? '1' : '0' }}">
                    <input type="hidden" name="reminder_time" value="{{ $company->reminder_time ?? '07:00' }}">
                    <input type="hidden" name="reminder_message" value="{{ $company->reminder_message ?? 'N\'oubliez pas de badger votre arrivée !' }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="time_rounding" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Arrondi des heures *</label>
                            <select name="time_rounding" id="time_rounding" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="minute" {{ $company->time_rounding === 'minute' ? 'selected' : '' }}>À la minute exacte (aucun arrondi)</option>
                                <option value="5min" {{ $company->time_rounding === '5min' ? 'selected' : '' }}>Aux 5 minutes les plus proches</option>
                                <option value="15min" {{ $company->time_rounding === '15min' ? 'selected' : '' }}>Au quart d'heure (15 min)</option>
                            </select>
                        </div>
                        <div>
                            <label for="timezone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fuseau horaire *</label>
                            <input type="text" name="timezone" id="timezone" required value="{{ old('timezone', $company->timezone ?? 'Europe/Paris') }}"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        </div>
                        <div>
                            <label for="break_threshold_hours" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Seuil de shift pour déduire la pause (heures)</label>
                            <input type="number" step="0.5" min="0" max="24" name="break_threshold_hours" id="break_threshold_hours" required
                                   value="{{ old('break_threshold_hours', $company->break_threshold_hours ?? 6) }}"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">Exemple : 6.0 h (si la journée dépasse 6h, la pause est déduite)</p>
                        </div>
                        <div>
                            <label for="break_deduction_minutes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Durée de pause déduite (minutes)</label>
                            <input type="number" step="5" min="0" max="180" name="break_deduction_minutes" id="break_deduction_minutes" required
                                   value="{{ old('break_deduction_minutes', $company->break_deduction_minutes ?? 30) }}"
                                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">Exemple : 30 min (déduit automatiquement 30 min sur le cumul)</p>
                        </div>
                    </div>

                    {{-- Section Horaires de Travail & Heures Supplémentaires --}}
                    <div class="border-t border-slate-100 pt-5 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
                                <i class="fa-solid fa-business-time"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Horaires Normaux & Base de Comparaison</h3>
                                <p class="text-xs text-slate-500">Heures de référence pour mesurer les retards et les heures supplémentaires</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="work_start_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                    Heure d'arrivée normale (Début de poste) *
                                </label>
                                <input type="time" name="work_start_time" id="work_start_time" required
                                       value="{{ old('work_start_time', $company->work_start_time ?? '08:00') }}"
                                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <p class="text-[11px] text-slate-400 mt-1">Tout pointage au-delà est comptabilisé comme retard dans les rapports.</p>
                            </div>
                            <div>
                                <label for="work_end_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                    Heure de départ normale (Fin de poste) *
                                </label>
                                <input type="time" name="work_end_time" id="work_end_time" required
                                       value="{{ old('work_end_time', $company->work_end_time ?? '17:00') }}"
                                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <p class="text-[11px] text-slate-400 mt-1">Heure théorique de fin de journée de travail.</p>
                            </div>
                        </div>

                        {{-- Toggle Heures Supplémentaires --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div class="pr-4">
                                <div class="text-sm font-bold text-slate-900">Calculer les heures supplémentaires</div>
                                <div class="text-xs text-slate-500 mt-0.5">Comptabilise le temps effectué après l'heure normale de fin dans les exports de paie</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="hidden" name="overtime_enabled" value="0">
                                <input type="checkbox" name="overtime_enabled" value="1"
                                       {{ $company->overtime_enabled ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        {{-- Toggle Exiger présence sur site pour les rapports --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div class="pr-4">
                                <div class="text-sm font-bold text-slate-900">Rapports : Comptabiliser uniquement les pointages sur site</div>
                                <div class="text-xs text-slate-500 mt-0.5">Si coché, les shifts pointés à distance / hors zone ne sont pas totalisés dans les heures payées</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="hidden" name="require_onsite_for_reports" value="0">
                                <input type="checkbox" name="require_onsite_for_reports" value="1"
                                       {{ $company->require_onsite_for_reports ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>

                    {{-- Section Anti-Double Pointage / Délai Minimum (Demandé par l'utilisateur) --}}
                    <div class="border-t border-slate-100 pt-5 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Anti-Double Pointage & Délai de Latence</h3>
                                <p class="text-xs text-slate-500">Empêche un employé de pointer son départ immédiatement après son arrivée</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <div class="pr-4">
                                <div class="text-sm font-bold text-slate-900">Activer le délai minimum entre Arrivée et Départ</div>
                                <div class="text-xs text-slate-500 mt-0.5">Si activé, l'employé doit attendre le délai configuré ci-dessous avant de pouvoir valider son départ</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="hidden" name="cooldown_enabled" value="0">
                                <input type="checkbox" name="cooldown_enabled" value="1"
                                       {{ $company->cooldown_enabled ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="min_punch_interval_minutes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                    Délai minimum avant départ (en minutes) *
                                </label>
                                <input type="number" min="5" max="720" name="min_punch_interval_minutes" id="min_punch_interval_minutes" required
                                       value="{{ old('min_punch_interval_minutes', $company->min_punch_interval_minutes ?? 60) }}"
                                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <p class="text-[11px] text-slate-400 mt-1">Par défaut : 60 minutes (1 heure). Bloque les pointages erronés consécutifs.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm shadow transition">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================
             ONGLET 3 : ACCÈS & BORNES KIOSK
        ============================================================ --}}
        <div x-show="activeTab === 'acces'" x-cloak class="space-y-6">

            {{-- Flash borne créée ou clé régénérée --}}
            @if(session('kiosk_created'))
                <div class="p-5 bg-violet-50 border-2 border-violet-400 rounded-2xl space-y-3">
                    <div class="flex items-center text-violet-900 font-bold text-sm">
                        <i class="fa-solid fa-circle-check text-violet-600 mr-2 text-base"></i> Clé d'accès générée — Notez ces informations maintenant !
                    </div>
                    <div class="font-mono text-xs bg-white p-4 rounded-xl border border-violet-200 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-sans">Identifiant Borne :</span>
                            <span class="font-bold text-violet-800 tracking-widest text-sm select-all">{{ session('kiosk_created')['device_code'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-sans">Clé d'accès (6 chiffres) :</span>
                            <span class="font-black text-violet-800 text-2xl tracking-[0.4em] select-all">{{ session('kiosk_created')['access_key'] }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-violet-700 font-medium">⚠️ La clé d'accès ne sera plus réaffichée. Utilisez l'écran de connexion « Mode Borne » sur le téléphone dédié.</p>
                </div>
            @endif

            {{-- Toggles Mode Accès --}}
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Modes de Connexion pour les Salariés</h2>
                        <p class="text-xs text-slate-500">Choisissez comment vos agents accèdent à l'application mobile</p>
                    </div>
                </div>

                <form action="{{ route('admin.company.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $company->name }}">
                    <input type="hidden" name="address" value="{{ $company->address }}">
                    <input type="hidden" name="latitude" value="{{ $company->latitude }}">
                    <input type="hidden" name="longitude" value="{{ $company->longitude }}">
                    <input type="hidden" name="time_rounding" value="{{ $company->time_rounding ?? 'minute' }}">
                    <input type="hidden" name="break_threshold_hours" value="{{ $company->break_threshold_hours ?? 6 }}">
                    <input type="hidden" name="break_deduction_minutes" value="{{ $company->break_deduction_minutes ?? 30 }}">
                    <input type="hidden" name="cooldown_enabled" value="{{ $company->cooldown_enabled ? '1' : '0' }}">
                    <input type="hidden" name="min_punch_interval_minutes" value="{{ $company->min_punch_interval_minutes ?? 60 }}">
                    <input type="hidden" name="work_start_time" value="{{ $company->work_start_time ?? '08:00' }}">
                    <input type="hidden" name="work_end_time" value="{{ $company->work_end_time ?? '17:00' }}">
                    <input type="hidden" name="overtime_enabled" value="{{ $company->overtime_enabled ? '1' : '0' }}">
                    <input type="hidden" name="require_onsite_for_reports" value="{{ $company->require_onsite_for_reports ? '1' : '0' }}">
                    <input type="hidden" name="timezone" value="{{ $company->timezone ?? 'Europe/Paris' }}">
                    <input type="hidden" name="reminder_enabled" value="{{ $company->reminder_enabled ? '1' : '0' }}">
                    <input type="hidden" name="reminder_time" value="{{ $company->reminder_time ?? '07:00' }}">
                    <input type="hidden" name="reminder_message" value="{{ $company->reminder_message ?? 'N\'oubliez pas de badger votre arrivée !' }}">

                    {{-- Toggle Connexion Individuelle --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                        <div class="flex items-start space-x-3">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-sm flex-shrink-0">
                                <i class="fa-solid fa-user-lock"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Connexion individuelle par email</div>
                                <div class="text-xs text-slate-500 mt-0.5">Chaque agent se connecte avec son email/mot de passe depuis son propre smartphone</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                            <input type="hidden" name="individual_login_enabled" value="0">
                            <input type="checkbox" name="individual_login_enabled" value="1"
                                   {{ $company->individual_login_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    {{-- Toggle Mode Borne/Kiosk --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                        <div class="flex items-start space-x-3">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center text-sm flex-shrink-0">
                                <i class="fa-solid fa-tablet-screen-button"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Mode Borne / Kiosk partagé</div>
                                <div class="text-xs text-slate-500 mt-0.5">Un appareil commun (tablette ou téléphone à l'accueil) utilisé par tous les salariés pour pointer au PIN ou QR</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                            <input type="hidden" name="kiosk_mode_enabled" value="0">
                            <input type="checkbox" name="kiosk_mode_enabled" value="1"
                                   {{ $company->kiosk_mode_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-semibold rounded-xl text-sm shadow transition">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Enregistrer les accès
                        </button>
                    </div>
                </form>
            </div>

            {{-- Section Bornes Configurées --}}
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Bornes de Pointage</h2>
                        <p class="text-xs text-slate-500">Chaque borne possède un identifiant unique pour connecter l'appareil</p>
                    </div>
                </div>

                {{-- Formulaire de création d'une borne --}}
                <form action="{{ route('admin.company.kiosks.store') }}" method="POST" class="flex flex-col sm:flex-row items-end space-y-3 sm:space-y-0 sm:space-x-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    @csrf
                    <div class="w-full flex-1">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom du poste / de la borne *</label>
                        <input type="text" name="name" required placeholder="Ex: Borne Entrée Principale, Borne Atelier 1..."
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-violet-600 focus:outline-none bg-white">
                    </div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold whitespace-nowrap shadow transition">
                        <i class="fa-solid fa-plus mr-1"></i> Créer la borne
                    </button>
                </form>

                {{-- Tableau des bornes --}}
                @if($kiosks->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                                    <th class="py-2.5 px-4">Nom de la borne</th>
                                    <th class="py-2.5 px-4">Identifiant (Device Code)</th>
                                    <th class="py-2.5 px-4">Statut</th>
                                    <th class="py-2.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($kiosks as $kiosk)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-3 px-4 font-semibold text-slate-800">{{ $kiosk->name }}</td>
                                        <td class="py-3 px-4 font-mono text-xs text-violet-700 font-bold tracking-widest select-all">
                                            {{ $kiosk->device_code }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $kiosk->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $kiosk->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            {{-- Regénérer clé --}}
                                            <form action="{{ route('admin.company.kiosks.regenerate-key', $kiosk) }}" method="POST" class="inline" onsubmit="return confirm('Regénérer une nouvelle clé d\'accès à 6 chiffres pour cette borne ?')">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 text-xs text-violet-700 bg-violet-50 hover:bg-violet-100 rounded-lg font-medium border border-violet-200 transition" title="Générer un nouveau code">
                                                    <i class="fa-solid fa-key mr-1"></i> Nouvelle clé
                                                </button>
                                            </form>
                                            {{-- Supprimer --}}
                                            <form action="{{ route('admin.company.kiosks.destroy', $kiosk) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer définitivement cette borne ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 text-xs text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg font-medium border border-rose-200 transition">
                                                    <i class="fa-solid fa-trash mr-1"></i> Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6 text-center text-slate-400 text-xs bg-slate-50 rounded-2xl border border-slate-100">
                        <i class="fa-solid fa-tablet-screen-button text-2xl text-slate-300 mb-2 block"></i>
                        Aucune borne configurée pour l'instant. Créez votre première borne ci-dessus.
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================
             ONGLET 4 : RAPPELS (Notifications Mobile)
        ============================================================ --}}
        <div x-show="activeTab === 'rappels'" x-cloak>
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Rappels de Pointage (Notifications Mobile)</h2>
                        <p class="text-xs text-slate-500">Les agents reçoivent une notification push à l'heure configurée pour leur rappeler de badger</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 leading-relaxed">
                    <p class="font-bold flex items-center text-sm mb-1"><i class="fa-solid fa-lightbulb mr-2 text-amber-500"></i> Comment ça marche ?</p>
                    <p>L'application mobile envoie automatiquement une notification aux agents à l'heure exacte choisie ci-dessous pour leur rappeler d'enregistrer leur arrivée.</p>
                </div>

                <form action="{{ route('admin.company.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $company->name }}">
                    <input type="hidden" name="address" value="{{ $company->address }}">
                    <input type="hidden" name="latitude" value="{{ $company->latitude }}">
                    <input type="hidden" name="longitude" value="{{ $company->longitude }}">
                    <input type="hidden" name="time_rounding" value="{{ $company->time_rounding ?? 'minute' }}">
                    <input type="hidden" name="break_threshold_hours" value="{{ $company->break_threshold_hours ?? 6 }}">
                    <input type="hidden" name="break_deduction_minutes" value="{{ $company->break_deduction_minutes ?? 30 }}">
                    <input type="hidden" name="cooldown_enabled" value="{{ $company->cooldown_enabled ? '1' : '0' }}">
                    <input type="hidden" name="min_punch_interval_minutes" value="{{ $company->min_punch_interval_minutes ?? 60 }}">
                    <input type="hidden" name="work_start_time" value="{{ $company->work_start_time ?? '08:00' }}">
                    <input type="hidden" name="work_end_time" value="{{ $company->work_end_time ?? '17:00' }}">
                    <input type="hidden" name="overtime_enabled" value="{{ $company->overtime_enabled ? '1' : '0' }}">
                    <input type="hidden" name="require_onsite_for_reports" value="{{ $company->require_onsite_for_reports ? '1' : '0' }}">
                    <input type="hidden" name="timezone" value="{{ $company->timezone ?? 'Europe/Paris' }}">
                    <input type="hidden" name="kiosk_mode_enabled" value="{{ $company->kiosk_mode_enabled ? '1' : '0' }}">
                    <input type="hidden" name="individual_login_enabled" value="{{ $company->individual_login_enabled ? '1' : '0' }}">

                    {{-- Toggle Activation --}}
                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                        <div class="flex items-start space-x-3">
                            <div class="mt-0.5 w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-sm flex-shrink-0">
                                <i class="fa-solid fa-bell-on"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Activer les rappels automatiques</div>
                                <div class="text-xs text-slate-500 mt-0.5">Les agents reçoivent un rappel push chaque matin à l'heure configurée</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                            <input type="hidden" name="reminder_enabled" value="0">
                            <input type="checkbox" name="reminder_enabled" value="1"
                                   {{ $company->reminder_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>

                    {{-- Multi-Rappels configurables --}}
                    @php
                        $initialReminders = $company->reminders ?? [
                            ['time' => $company->reminder_time ?? '07:00', 'message' => $company->reminder_message ?? 'N\'oubliez pas de badger votre arrivée !']
                        ];
                        if (empty($initialReminders)) {
                            $initialReminders = [['time' => '07:00', 'message' => 'N\'oubliez pas de badger votre arrivée !']];
                        }
                    @endphp
                    <div x-data="{ reminders: {{ json_encode($initialReminders) }} }" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Horaires & Messages des Rappels</label>
                            <button type="button" @click="reminders.push({ time: '17:00', message: 'N\'oubliez pas de badger votre départ !' })"
                                    class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center">
                                <i class="fa-solid fa-plus mr-1.5"></i> Ajouter un rappel
                            </button>
                        </div>
                        <p class="text-xs text-slate-500">Les rappels sont envoyés par le serveur, au fuseau horaire de l’entreprise, aux appareils qui ont activé les notifications. Le planificateur serveur doit rester actif.</p>

                        <div class="space-y-3">
                            <template x-for="(reminder, index) in reminders" :key="index">
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-center gap-3">
                                    <div class="w-full sm:w-36 flex-shrink-0">
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Heure</label>
                                        <input type="time" :name="'reminders[' + index + '][time]'" x-model="reminder.time" required
                                               class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-sm bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    </div>
                                    <div class="w-full flex-1">
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Notification / Message</label>
                                        <input type="text" :name="'reminders[' + index + '][message]'" x-model="reminder.message" maxlength="200"
                                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                               placeholder="Ex: N'oubliez pas de badger !">
                                    </div>
                                    <div class="w-full sm:w-auto flex sm:flex-col justify-end pt-3 sm:pt-4">
                                        <button type="button" @click="if (reminders.length > 1) { reminders.splice(index, 1); } else { alert('Vous devez conserver au moins un rappel.'); }"
                                                class="px-3 py-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl text-xs font-bold border border-rose-200 transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Aperçu visuel de la notification --}}
                    <div class="bg-slate-800 rounded-2xl p-4 text-white text-xs flex items-start space-x-3">
                        <img src="{{ asset('mobile/icons/icon.png') }}" class="w-10 h-10 rounded-xl flex-shrink-0 object-contain bg-white p-1" alt="">
                        <div>
                            <div class="font-bold text-sm">⏰ ChronoStaff — Rappel de pointage</div>
                            <div class="text-slate-300 mt-0.5">{{ $company->reminder_message ?? 'N\'oubliez pas de badger votre arrivée !' }}</div>
                            <div class="text-slate-500 text-[10px] mt-1">{{ $company->reminder_time ?? '07:00' }} · ChronoStaff</div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl text-sm shadow transition">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Enregistrer les rappels
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================
             ONGLET 5 : MANAGERS MOBILES
        ============================================================ --}}
        <div x-show="activeTab === 'managers'" x-cloak>
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Second Administrateur & Managers Mobiles</h2>
                        <p class="text-xs text-slate-500">Superviseurs accédant au dashboard mobile (équipe présente en temps réel)</p>
                    </div>
                </div>

                <form action="{{ route('admin.company.managers.store') }}" method="POST" class="bg-slate-50 p-5 rounded-2xl border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nom complet *</label>
                        <input type="text" name="name" required placeholder="Ex: Marc Dubois"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-600 focus:outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email de connexion *</label>
                        <input type="email" name="email" required placeholder="adjoint@entreprise.com"
                               class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-600 focus:outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mot de passe *</label>
                        <div class="flex space-x-2">
                            <input type="password" name="password" required minlength="6" placeholder="••••••••"
                                   class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-600 focus:outline-none bg-white">
                            <button type="submit" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold whitespace-nowrap shadow transition">
                                <i class="fa-solid fa-plus mr-1"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200">
                                <th class="py-2.5 px-4">Nom</th>
                                <th class="py-2.5 px-4">Email</th>
                                <th class="py-2.5 px-4">Rôle</th>
                                <th class="py-2.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($managers as $manager)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-4 font-semibold text-slate-800">{{ $manager->name }}</td>
                                    <td class="py-3 px-4 text-slate-600 text-xs">{{ $manager->email }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Manager Mobile</span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <form action="{{ route('admin.company.managers.destroy', $manager) }}" method="POST" class="inline" onsubmit="return confirm('Révoquer cet accès manager ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium">
                                                <i class="fa-solid fa-trash mr-1"></i> Révoquer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-slate-400 text-xs">Aucun manager mobile configuré pour l'instant.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================================
             ONGLET 6 : MON COMPTE & MOT DE PASSE ADMIN
        ============================================================ --}}
        <div x-show="activeTab === 'securite'" x-cloak>
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Mon Compte Administrateur & Sécurité</h2>
                        <p class="text-xs text-slate-500">Modifiez votre mot de passe pour sécuriser l'accès à votre espace d'administration</p>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-500 block">Administrateur connecté :</span>
                        <span class="font-bold text-slate-800 text-sm">{{ Auth::user()->name }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 block">Email :</span>
                        <span class="font-mono text-slate-800 font-semibold">{{ Auth::user()->email }}</span>
                    </div>
                </div>

                <form action="{{ route('admin.company.password.update') }}" method="POST" class="space-y-4 max-w-lg">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mot de passe actuel *</label>
                        <input type="password" name="current_password" id="current_password" required placeholder="••••••••"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nouveau mot de passe (min. 6 caractères) *</label>
                        <input type="password" name="password" id="password" required minlength="6" placeholder="••••••••"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirmer le nouveau mot de passe *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6" placeholder="••••••••"
                               class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow transition">
                            <i class="fa-solid fa-lock mr-2"></i> Mettre à jour mon mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- /x-data --}}
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let mapInitialized = false;
    function initMap() {
        if (mapInitialized) return;
        const mapEl = document.getElementById('company-map');
        if (!mapEl || mapEl.offsetParent === null) return;
        mapInitialized = true;

        const defaultLat = parseFloat(document.getElementById('latitude')?.value) || 48.8698;
        const defaultLng = parseFloat(document.getElementById('longitude')?.value) || 2.3314;

        const map = L.map('company-map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value = pos.lat.toFixed(8);
            document.getElementById('longitude').value = pos.lng.toFixed(8);
        });
        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
        });
        setTimeout(() => map.invalidateSize(), 150);
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initMap, 200);
        document.querySelectorAll('[\\@click]').forEach(btn => {
            btn.addEventListener('click', () => setTimeout(initMap, 150));
        });
    });

    window.searchAddress = function() {
        const addr = document.getElementById('address')?.value;
        if (!addr) return;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addr)}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    document.getElementById('latitude').value = lat.toFixed(8);
                    document.getElementById('longitude').value = lon.toFixed(8);
                } else {
                    alert('Adresse introuvable. Cliquez directement sur la carte.');
                }
            })
            .catch(err => console.error(err));
    };
</script>
@endpush
