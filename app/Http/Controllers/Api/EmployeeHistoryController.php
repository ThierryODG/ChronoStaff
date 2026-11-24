<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeHistoryController extends Controller
{
    /**
     * Get clock-in/out history for the authenticated employee.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = TimeLog::with('department')
            ->where('user_id', $user->id)
            ->orderByDesc('punched_at');

        if ($request->has('from_date')) {
            $query->whereDate('punched_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('punched_at', '<=', $request->input('to_date'));
        }

        $logs = $query->paginate($request->input('per_page', 20));

        // Format items for Flutter app display
        $formatted = collect($logs->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'type' => $log->type, // 'in' or 'out'
                'punched_at' => $log->punched_at->toIso8601String(),
                'date_formatted' => $log->punched_at->format('d/m/Y'),
                'time_formatted' => $log->punched_at->format('H:i'),
                'department_name' => $log->department?->name ?? 'Général',
                'is_on_site' => (bool) $log->is_on_site,
                'distance_meters' => $log->distance_meters,
                'is_manual' => (bool) $log->is_manual,
                'admin_note' => $log->admin_note,
                'is_synced' => true, // Returned by server -> confirmed synced
                'offline_id' => $log->offline_id,
            ];
        });

        return response()->json([
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
            'data' => $formatted,
        ]);
    }
}

