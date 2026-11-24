<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Justification;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminManualAdjustmentController extends Controller
{
    public function index(): View
    {
        $admin = Auth::user();
        $companyId = $admin->company_id;

        $manualLogs = TimeLog::where('is_manual', true)
            ->whereHas('user', fn($q) => $companyId ? $q->where('company_id', $companyId) : $q)
            ->with(['user', 'department'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $justifications = Justification::whereHas('user', fn($q) => $companyId ? $q->where('company_id', $companyId) : $q)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $employees = User::where('role', 'employee')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        $departments = Department::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();

        return view('admin.adjustments.index', compact('manualLogs', 'justifications', 'employees', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'required|in:in,out',
            'punch_date' => 'required|date',
            'punch_time' => 'required|string',
            'admin_note' => 'required|string|min:5|max:500', // Justification obligatoire !
        ]);

        $punchedAt = Carbon::parse($validated['punch_date'] . ' ' . $validated['punch_time']);

        TimeLog::create([
            'user_id' => $validated['user_id'],
            'department_id' => $validated['department_id'] ?? null,
            'type' => $validated['type'],
            'punched_at' => $punchedAt,
            'is_manual' => true,
            'admin_note' => $validated['admin_note'],
            'is_on_site' => true,
            'synced_at' => now(),
        ]);

        return back()->with('success', 'Ajustement manuel enregistré avec motif obligatoire conservé.');
    }

    public function updateJustificationStatus(Request $request, Justification $justification): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_comment' => 'nullable|string|max:500',
        ]);

        $justification->update([
            'status' => $validated['status'],
            'admin_comment' => $validated['admin_comment'] ?? null,
        ]);

        $statusLabel = $validated['status'] === 'approved' ? 'approuvée' : 'rejetée';
        return back()->with('success', "La justification de l'employé a été {$statusLabel}.");
    }
}
