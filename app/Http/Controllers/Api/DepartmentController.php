<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $departments = Department::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $departments]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id ?? 1,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Département créé avec succès.',
            'department' => $department,
        ], 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $department->update($validated);

        return response()->json([
            'message' => 'Département mis à jour avec succès.',
            'department' => $department,
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json([
            'message' => 'Département supprimé avec succès.',
        ]);
    }
}

