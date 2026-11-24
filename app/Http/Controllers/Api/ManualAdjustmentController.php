<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualAdjustmentController extends Controller
{
    /**
     * Create a manual punch entry (e.g., employee forgot to clock in).
     * Mandatory admin_note.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'required|in:in,out',
            'punched_at' => 'required|date',
            'admin_note' => 'required|string|min:5|max:500', // Mandatory justification
        ]);

        $timeLog = TimeLog::create([
            'user_id' => $validated['user_id'],
            'department_id' => $validated['department_id'] ?? null,
            'type' => $validated['type'],
            'punched_at' => Carbon::parse($validated['punched_at']),
            'is_manual' => true,
            'admin_note' => $validated['admin_note'],
            'is_on_site' => true,
            'synced_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ajustement manuel enregistré avec succès.',
            'time_log' => $timeLog->load(['user', 'department']),
        ], 201);
    }

    /**
     * Update an existing time log with justification.
     */
    public function update(Request $request, TimeLog $timeLog): JsonResponse
    {
        $validated = $request->validate([
            'punched_at' => 'required|date',
            'type' => 'required|in:in,out',
            'department_id' => 'nullable|exists:departments,id',
            'admin_note' => 'required|string|min:5|max:500', // Mandatory note
        ]);

        $timeLog->update([
            'punched_at' => Carbon::parse($validated['punched_at']),
            'type' => $validated['type'],
            'department_id' => $validated['department_id'] ?? $timeLog->department_id,
            'is_manual' => true,
            'admin_note' => $validated['admin_note'],
        ]);

        return response()->json([
            'message' => 'Pointage modifié avec succès.',
            'time_log' => $timeLog->fresh()->load(['user', 'department']),
        ]);
    }
}
