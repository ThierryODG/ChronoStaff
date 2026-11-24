<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeManagementController extends Controller
{
    /**
     * List all employees of the company.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();

        $employees = User::where('role', 'employee')
            ->when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))
            ->with(['departments', 'latestTimeLog'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $employees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'email' => $emp->email,
                    'pin_code' => $emp->pin_code,
                    'qr_token' => $emp->qr_token,
                    'is_active' => $emp->is_active,
                    'departments' => $emp->departments,
                    'current_status' => $emp->latestTimeLog?->type === 'in' ? 'checked_in' : 'checked_out',
                    'latest_punch' => $emp->latestTimeLog,
                ];
            }),
        ]);
    }

    /**
     * Create a new employee with auto-generated PIN and QR token.
     */
    public function store(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'pin_code' => 'nullable|string|size:4|unique:users,pin_code',
            'departments' => 'nullable|array',
            'departments.*.id' => 'required|exists:departments,id',
            'departments.*.hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $credentials = User::generateCredentials();

        $employee = User::create([
            'company_id' => $admin->company_id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password'] ?? Str::random(12)),
            'role' => 'employee',
            'pin_code' => $validated['pin_code'] ?? $credentials['pin_code'],
            'qr_token' => $credentials['qr_token'],
            'is_active' => true,
        ]);

        // Attach departments
        if (!empty($validated['departments'])) {
            $syncData = [];
            foreach ($validated['departments'] as $dept) {
                $syncData[$dept['id']] = ['hourly_rate' => $dept['hourly_rate'] ?? null];
            }
            $employee->departments()->sync($syncData);
        }

        return response()->json([
            'message' => 'Employé créé avec succès.',
            'employee' => $employee->load('departments'),
        ], 201);
    }

    /**
     * Show a single employee.
     */
    public function show(User $employee): JsonResponse
    {
        return response()->json([
            'employee' => $employee->load(['departments', 'latestTimeLog']),
        ]);
    }

    /**
     * Update an employee.
     */
    public function update(Request $request, User $employee): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $employee->id,
            'pin_code' => 'nullable|string|size:4|unique:users,pin_code,' . $employee->id,
            'is_active' => 'sometimes|boolean',
            'departments' => 'nullable|array',
            'departments.*.id' => 'required|exists:departments,id',
            'departments.*.hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $employee->update(collect($validated)->except('departments')->toArray());

        if (isset($validated['departments'])) {
            $syncData = [];
            foreach ($validated['departments'] as $dept) {
                $syncData[$dept['id']] = ['hourly_rate' => $dept['hourly_rate'] ?? null];
            }
            $employee->departments()->sync($syncData);
        }

        return response()->json([
            'message' => 'Employé mis à jour avec succès.',
            'employee' => $employee->fresh()->load('departments'),
        ]);
    }

    /**
     * Deactivate / delete employee.
     */
    public function destroy(User $employee): JsonResponse
    {
        // Deactivate rather than hard delete to preserve history
        $employee->update(['is_active' => false]);

        return response()->json([
            'message' => 'Employé désactivé avec succès.',
        ]);
    }

    /**
     * Regenerate QR Code token for an employee.
     */
    public function regenerateQr(User $employee): JsonResponse
    {
        $employee->update(['qr_token' => Str::random(32)]);

        return response()->json([
            'message' => 'Nouveau QR Code généré.',
            'qr_token' => $employee->qr_token,
        ]);
    }
}

