<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminEmployeeController extends Controller
{
    public function index(): View
    {
        $admin = Auth::user();
        $companyId = $admin->company_id;

        $employees = User::where('role', 'employee')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with(['departments', 'latestTimeLog'])
            ->orderBy('name')
            ->paginate(30);

        $justifications = Justification::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with('user')
            ->orderByDesc('date')
            ->get();

        $departments = Department::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();

        return view('admin.employees.index', compact('employees', 'justifications', 'departments'));
    }

    public function create(): View
    {
        $admin = Auth::user();
        $departments = Department::when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))->get();
        $suggestedCredentials = User::generateCredentials();

        return view('admin.employees.create', compact('departments', 'suggestedCredentials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'pin_code' => 'required|string|size:4|unique:users,pin_code',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $credentials = User::generateCredentials();

        $employee = User::create([
            'company_id' => $admin->company_id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password'] ?? Str::random(12)),
            'role' => 'employee',
            'pin_code' => $validated['pin_code'],
            'qr_token' => $credentials['qr_token'],
            'is_active' => true,
        ]);

        if (!empty($validated['department_ids'])) {
            $syncData = [];
            foreach ($validated['department_ids'] as $deptId) {
                $syncData[$deptId] = ['hourly_rate' => $validated['hourly_rate'] ?? null];
            }
            $employee->departments()->sync($syncData);
        }

        return redirect()->route('admin.employees.index')->with('success', 'Employé "' . $employee->name . '" créé avec succès.');
    }

    public function edit(User $employee): View
    {
        $admin = Auth::user();
        $departments = Department::when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))->get();

        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $employee->id,
            'pin_code' => 'required|string|size:4|unique:users,pin_code,' . $employee->id,
            'is_active' => 'required|boolean',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'hourly_rate' => 'nullable|numeric|min:0',
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'pin_code' => $validated['pin_code'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $employee->update($updateData);

        if (isset($validated['department_ids'])) {
            $syncData = [];
            foreach ($validated['department_ids'] as $deptId) {
                $syncData[$deptId] = ['hourly_rate' => $validated['hourly_rate'] ?? null];
            }
            $employee->departments()->sync($syncData);
        }

        return redirect()->route('admin.employees.index')->with('success', 'Employé mis à jour avec succès.');
    }

    public function toggleStatus(User $employee): RedirectResponse
    {
        $employee->update(['is_active' => !$employee->is_active]);

        $statusText = $employee->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "Employé {$statusText} avec succès.");
    }

    public function regenerateQr(User $employee): RedirectResponse
    {
        $employee->update(['qr_token' => Str::random(32)]);

        return back()->with('success', 'Nouveau QR Code généré pour ' . $employee->name);
    }

    public function badge(User $employee): View
    {
        $employee->load(['departments', 'company']);
        return view('admin.employees.badge', compact('employee'));
    }
}

