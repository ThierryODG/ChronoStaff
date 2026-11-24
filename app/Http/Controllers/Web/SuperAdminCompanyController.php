<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SuperAdminCompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::with(['users' => function ($q) {
            $q->orderBy('id');
        }])
        ->withCount(['users', 'timeLogs'])
        ->orderByDesc('id')
        ->get();

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => PHP_OS_FAMILY,
            'environment' => config('app.env'),
            'total_companies' => $companies->count(),
            'total_users' => User::count(),
        ];

        return view('superadmin.companies.index', compact('companies', 'systemInfo'));
    }

    public function subscriptions(): View
    {
        return view('superadmin.subscriptions.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $company = Company::create([
            'name' => $validated['company_name'],
            'address' => $validated['address'] ?? null,
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'overtime_enabled' => true,
            'require_onsite_for_reports' => true,
            'timezone' => 'Europe/Paris',
        ]);

        Department::create([
            'company_id' => $company->id,
            'name' => 'Général',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'role' => 'admin',
            'is_active' => true,
            'terms_accepted_at' => now(),
        ]);

        return back()->with('success', "L'entreprise « {$company->name} » et son administrateur ont été créés avec succès.");
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $adminUser = User::where('company_id', $company->id)->where('role', 'admin')->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email,' . ($adminUser->id ?? 0),
            'admin_password' => 'nullable|string|min:6',
        ]);

        $company->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $company->address,
        ]);

        if ($adminUser) {
            $adminUser->name = $validated['admin_name'];
            $adminUser->email = $validated['admin_email'];
            if (!empty($validated['admin_password'])) {
                $adminUser->password = Hash::make($validated['admin_password']);
            }
            $adminUser->save();
        }

        return back()->with('success', "Informations de l'entreprise « {$company->name} » mises à jour.");
    }

    public function destroy(Company $company): RedirectResponse
    {
        $name = $company->name;
        $company->delete();

        return back()->with('success', "L'entreprise « {$name} » et toutes ses données associées ont été supprimées.");
    }

    public function impersonate(Company $company): RedirectResponse
    {
        $adminUser = User::where('company_id', $company->id)->where('role', 'admin')->first();

        if (!$adminUser) {
            return back()->withErrors(['error' => "Aucun compte administrateur trouvé pour cette entreprise."]);
        }

        session(['impersonator_id' => Auth::id()]);
        Auth::login($adminUser);

        return redirect()->route('admin.dashboard')->with('success', "Vous êtes maintenant connecté en tant qu'administrateur de {$company->name}.");
    }

    public function leaveImpersonation(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if ($impersonatorId) {
            $superAdmin = User::find($impersonatorId);
            if ($superAdmin) {
                Auth::login($superAdmin);
                session()->forget('impersonator_id');
                return redirect()->route('superadmin.companies.index')->with('success', "Retour à l'espace Super-Administrateur.");
            }
        }

        return redirect()->route('admin.dashboard');
    }

    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            return back()->with('success', 'Nettoyage système effectué : caches de configuration, de routes et de vues compilées purgés avec succès.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Erreur lors du nettoyage : ' . $e->getMessage()]);
        }
    }
}
