<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Kiosk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCompanyController extends Controller
{
    public function edit(): View
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::firstOrCreate(
            ['id' => 1],
            ['name' => 'ChronoStaff Entreprise']
        );

        $managers = User::where('role', 'manager')
            ->when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))
            ->get();

        $kiosks = Kiosk::when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))->get();

        return view('admin.company.edit', compact('company', 'managers', 'kiosks'));
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'time_rounding' => 'required|in:minute,5min,15min',
            'break_threshold_hours' => 'required|numeric|min:0|max:24',
            'break_deduction_minutes' => 'required|integer|min:0|max:180',
            'cooldown_enabled' => 'nullable|boolean',
            'min_punch_interval_minutes' => 'nullable|integer|min:1|max:720',
            'work_start_time' => 'nullable|regex:/^\d{2}:\d{2}$/',
            'work_end_time' => 'nullable|regex:/^\d{2}:\d{2}$/',
            'overtime_enabled' => 'nullable|boolean',
            'require_onsite_for_reports' => 'nullable|boolean',
            'timezone' => 'required|string|timezone',
            'kiosk_mode_enabled' => 'nullable|boolean',
            'individual_login_enabled' => 'nullable|boolean',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_time' => 'nullable|regex:/^\d{2}:\d{2}$/',
            'reminder_message' => 'nullable|string|max:200',
            'reminders' => 'nullable|array',
            'reminders.*.time' => 'nullable|regex:/^\d{2}:\d{2}$/',
            'reminders.*.message' => 'nullable|string|max:200',
        ]);

        $validated['kiosk_mode_enabled'] = $request->boolean('kiosk_mode_enabled');
        $validated['individual_login_enabled'] = $request->boolean('individual_login_enabled');
        $validated['reminder_enabled'] = $request->boolean('reminder_enabled');
        $validated['cooldown_enabled'] = $request->boolean('cooldown_enabled');
        $validated['overtime_enabled'] = $request->boolean('overtime_enabled');
        $validated['require_onsite_for_reports'] = $request->boolean('require_onsite_for_reports');
        $validated['min_punch_interval_minutes'] = (int) ($request->input('min_punch_interval_minutes', 60));

        // Filtrer et nettoyer la liste des rappels multiples
        if ($request->has('reminders')) {
            $cleanedReminders = [];
            foreach ($request->input('reminders', []) as $r) {
                if (!empty($r['time'])) {
                    $cleanedReminders[] = [
                        'time' => $r['time'],
                        'message' => !empty($r['message']) ? trim($r['message']) : 'Rappel de pointage ChronoStaff',
                    ];
                }
            }
            $validated['reminders'] = $cleanedReminders;
        }

        $company->update($validated);

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    public function storeManager(Request $request): RedirectResponse
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'company_id' => $admin->company_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'manager',
            'is_active' => true,
            'terms_accepted_at' => now(),
        ]);

        return back()->with('success', 'Second administrateur / Manager mobile créé avec succès.');
    }

    public function destroyManager(User $manager): RedirectResponse
    {
        if ($manager->role !== 'manager') {
            return back()->withErrors(['error' => 'Action non autorisée.']);
        }

        $manager->delete();
        return back()->with('success', 'Accès du manager révoqué avec succès.');
    }

    public function storeKiosk(Request $request): RedirectResponse
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $deviceCode = Kiosk::generateDeviceCode();
        $accessKey = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        Kiosk::create([
            'company_id' => $admin->company_id ?? 1,
            'name' => $validated['name'],
            'device_code' => $deviceCode,
            'access_key' => Hash::make($accessKey),
            'is_active' => true,
        ]);

        return back()
            ->with('success', "Borne '{$validated['name']}' créée avec succès.")
            ->with('kiosk_created', [
                'device_code' => $deviceCode,
                'access_key'  => $accessKey,
            ]);
    }

    public function regenerateKioskKey(Kiosk $kiosk): RedirectResponse
    {
        $accessKey = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $kiosk->update([
            'access_key' => Hash::make($accessKey),
        ]);

        return back()
            ->with('success', "Nouvelle clé d'accès générée pour la borne '{$kiosk->name}'.")
            ->with('kiosk_created', [
                'device_code' => $kiosk->device_code,
                'access_key'  => $accessKey,
            ]);
    }

    public function destroyKiosk(Kiosk $kiosk): RedirectResponse
    {
        $kiosk->delete();
        return back()->with('success', 'Poste fixe / Borne supprimé avec succès.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Votre mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Votre mot de passe a été mis à jour avec succès.');
    }
}
