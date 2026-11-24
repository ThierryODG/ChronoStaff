<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Realtime dashboard: "Qui est là ?" (Who is here).
     * Lists employees currently clocked 'in'.
     */
    public function whoIsHere(Request $request): JsonResponse
    {
        $admin = $request->user();
        $companyId = $admin->company_id;

        // Query all active employees of this company
        $employees = User::where('role', 'employee')
            ->where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with(['departments', 'company'])
            ->get();

        $presentList = [];
        $absentList = [];
        $lateCount = 0;
        $totalHoursToday = 0;

        $workStartTime = $admin->company?->work_start_time ?? '08:00';

        foreach ($employees as $emp) {
            $latest = TimeLog::where('user_id', $emp->id)
                ->orderByDesc('punched_at')
                ->first();

            // Check if first punch of today was late
            $firstToday = TimeLog::where('user_id', $emp->id)
                ->where('type', 'in')
                ->whereDate('punched_at', today())
                ->orderBy('punched_at')
                ->first();

            if ($firstToday) {
                $punchTime = $firstToday->punched_at->format('H:i');
                if ($punchTime > $workStartTime) {
                    $lateCount++;
                }
            }

            if ($latest && $latest->type === 'in') {
                $durationMinutes = $latest->punched_at->diffInMinutes(now());
                $hours = floor($durationMinutes / 60);
                $mins = $durationMinutes % 60;
                $totalHoursToday += ($durationMinutes / 60);

                $presentList[] = [
                    'employee_id' => $emp->id,
                    'name' => $emp->name,
                    'email' => $emp->email,
                    'department' => $latest->department?->name ?? $emp->departments->first()?->name ?? 'Général',
                    'clock_in_time' => $latest->punched_at->format('H:i'),
                    'clock_in_date' => $latest->punched_at->format('d/m/Y'),
                    'elapsed_time' => sprintf('%dh %02dm', $hours, $mins),
                    'is_on_site' => (bool) $latest->is_on_site,
                    'distance_meters' => $latest->distance_meters,
                    'location_label' => $latest->is_on_site ? 'Sur site' : 'À distance',
                ];
            } else {
                $absentList[] = [
                    'employee_id' => $emp->id,
                    'name' => $emp->name,
                    'department' => $emp->departments->first()?->name ?? 'Général',
                    'last_seen' => $latest ? $latest->punched_at->format('d/m/Y H:i') : 'Jamais',
                ];
            }
        }

        $presentCount = count($presentList);
        $punctualityRate = $presentCount > 0 
            ? max(0, round((($presentCount - $lateCount) / $presentCount) * 100)) 
            : 100;

        // Fetch recent justifications
        $recentJustifications = \App\Models\Justification::where('company_id', $companyId)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($j) {
                return [
                    'id' => $j->id,
                    'employee_name' => $j->user?->name ?? 'Employé',
                    'date' => $j->date?->format('d/m/Y') ?? '—',
                    'type' => $j->type,
                    'reason' => $j->reason,
                    'status' => $j->status,
                    'created_at' => $j->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'summary' => [
                'total_employees' => $employees->count(),
                'currently_present' => count($presentList),
                'currently_absent' => count($absentList),
                'punctuality_rate' => $punctualityRate,
                'total_hours_today' => round($totalHoursToday, 1),
                'late_arrivals_count' => $lateCount,
                'work_schedule' => [
                    'start' => $admin->company?->work_start_time ?? '08:00',
                    'end' => $admin->company?->work_end_time ?? '17:00',
                ],
                'refreshed_at' => now()->toIso8601String(),
            ],
            'present_employees' => $presentList,
            'absent_employees' => $absentList,
            'recent_justifications' => $recentJustifications,
        ]);
    }
}

