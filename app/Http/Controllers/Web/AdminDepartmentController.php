<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDepartmentController extends Controller
{
    public function index(): View
    {
        $admin = Auth::user();
        $departments = Department::when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        Department::create([
            'company_id' => $admin->company_id ?? 1,
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Département ajouté avec succès.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $department->update($validated);

        return back()->with('success', 'Département modifié avec succès.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->count() > 0) {
            return back()->withErrors(['error' => 'Impossible de supprimer un département contenant des employés assignés.']);
        }

        $department->delete();
        return back()->with('success', 'Département supprimé.');
    }
}

